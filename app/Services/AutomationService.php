<?php

namespace App\Services;

use App\Models\Automation;
use App\Models\AutomationRun;
use App\Models\Column;
use App\Models\GlobalLabel;
use App\Models\Task;
use App\Models\TaskLabel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AutomationService
{
    public static function run(string $eventType, array $context): void
    {
        try {
            $column = Column::find($context['column_id'] ?? null);
            if (! $column) {
                return;
            }

            $automations = Automation::where('board_id', $column->board_id)
                ->where('enabled', true)
                ->get();

            foreach ($automations as $auto) {
                if (! static::matchesTrigger($auto->trigger_type, $auto->trigger_config, $eventType, $context)) {
                    continue;
                }

                Log::info("[AUTOMATION] Running \"{$auto->name}\" (id={$auto->id}) for task {$context['task_id']}");
                $errors = static::executeActions($auto->actions, $context);

                $taskId = $context['task_id'] ?? null;
                $message = ($taskId ? "Triggered by task #{$taskId}" : 'Triggered')
                    .' → '.static::summariseActionTypes($auto->actions);
                static::record($auto, $errors, $message, ['task_id' => $taskId]);
            }
        } catch (\Throwable $e) {
            Log::error('[AUTOMATION] Error: '.$e->getMessage());
        }
    }

    /**
     * Returns true when a `schedule` automation's recurrence matches the given
     * moment. $now MUST already be expressed in the user's timezone.
     */
    public static function scheduleMatches(array $config, Carbon $now): bool
    {
        $time = $config['time'] ?? '09:00';
        if ($now->format('H:i') !== $time) {
            return false;
        }

        return match ($config['frequency'] ?? 'daily') {
            'weekly' => (int) ($config['day_of_week'] ?? 1) === $now->isoWeekday(),
            // Clamp so e.g. "31st" still fires on the last day of shorter months.
            'monthly' => min((int) ($config['day_of_month'] ?? 1), $now->daysInMonth) === $now->day,
            default => true, // daily
        };
    }

    /**
     * Run a time-based (`schedule`) automation. These have no task context, so
     * they use their own set of actions (create_card, bulk_move, due_cards,
     * archive_cards) rather than the per-task event actions.
     */
    public static function runScheduled(Automation $automation): void
    {
        Log::info("[AUTOMATION] Running scheduled \"{$automation->name}\" (id={$automation->id})");

        $summaries = [];
        $errors = [];

        foreach ($automation->actions as $action) {
            try {
                $summary = match ($action['type'] ?? null) {
                    'create_card' => static::actionCreateCard($action, $automation),
                    'bulk_move' => static::actionBulkMove($action, $automation),
                    'due_cards' => static::actionDueCards($action, $automation),
                    'archive_cards' => static::actionArchiveCards($action, $automation),
                    default => null,
                };
                if ($summary) {
                    $summaries[] = $summary;
                }
            } catch (\Throwable $e) {
                Log::error('[AUTOMATION] Scheduled action error: '.$e->getMessage());
                $errors[] = $e->getMessage();
            }
        }

        static::record($automation, $errors, $summaries ? implode('; ', $summaries) : 'Ran — no changes');
    }

    /**
     * Evaluate a `due_date_relative` automation: find every card on the board
     * whose due date is exactly N days before/after today, and run the
     * automation's (per-task) actions against each. Meant to run once per day.
     */
    public static function runDueDateRelative(Automation $automation): void
    {
        $config = $automation->trigger_config ?? [];
        $days = (int) ($config['days'] ?? 0);
        $today = now(config('app.user_timezone'))->startOfDay();
        $target = ($config['direction'] ?? 'before') === 'after'
            ? $today->copy()->subDays($days)
            : $today->copy()->addDays($days);

        $tasks = Task::whereIn('column_id', static::boardColumnIds($automation))
            ->whereNull('archived_at')
            ->whereDate('due_date', $target->toDateString())
            ->get();

        $errors = [];
        foreach ($tasks as $task) {
            Log::info("[AUTOMATION] due_date_relative \"{$automation->name}\" matched task {$task->id}");
            $errors = array_merge($errors, static::executeActions($automation->actions, ['task_id' => $task->id, 'column_id' => $task->column_id]));
        }

        $when = $days.' day(s) '.($config['direction'] ?? 'before').' due date';
        $message = "Matched {$tasks->count()} card(s) {$when} → ".static::summariseActionTypes($automation->actions);
        static::record($automation, $errors, $message, ['matched' => $tasks->count(), 'task_ids' => $tasks->pluck('id')->all()]);
    }

    private static function matchesTrigger(string $triggerType, array $config, string $eventType, array $context): bool
    {
        return match ($triggerType) {
            'card_done' => $eventType === 'task_completed',
            'card_deleted' => $eventType === 'task_deleted',
            'card_created' => $eventType === 'task_created' && (! isset($config['column_id']) || $config['column_id'] == $context['column_id']),
            'card_added_to', 'card_moved_into' => $eventType === 'task_moved' && ($context['new_column_id'] ?? null) == ($config['column_id'] ?? null),
            'card_moved_out' => $eventType === 'task_moved' && ($context['old_column_id'] ?? null) == ($config['column_id'] ?? null),
            'due_date_set' => $eventType === 'task_updated' && ($context['due_date_changed'] ?? false) && ! empty($context['new_due_date']),
            default => false,
        };
    }

    /**
     * Execute per-task (event) actions. Returns a list of error messages
     * (empty when everything succeeded).
     */
    private static function executeActions(array $actions, array $context): array
    {
        $errors = [];

        foreach ($actions as $action) {
            try {
                match ($action['type'] ?? null) {
                    'move_card' => static::actionMoveCard($action, $context),
                    'add_label' => static::actionAddLabel($action, $context),
                    'remove_due_date' => Task::where('id', $context['task_id'])->update(['due_date' => null]),
                    'update_due_date' => static::actionUpdateDueDate($action, $context),
                    'mark_done' => static::actionMarkDone($context),
                    default => null,
                };
            } catch (\Throwable $e) {
                Log::error('[AUTOMATION] Action error: '.$e->getMessage());
                $errors[] = $e->getMessage();
            }
        }

        return $errors;
    }

    /**
     * Persist an execution-history record for an automation. $errors is the
     * list of error messages collected during the run (empty = success).
     * Keeps only the most recent 100 runs per automation.
     */
    private static function record(Automation $automation, array $errors, string $message, array $details = []): void
    {
        try {
            if ($errors) {
                $message .= ' (errors: '.implode('; ', $errors).')';
                $details['errors'] = $errors;
            }

            AutomationRun::create([
                'automation_id' => $automation->id,
                'status' => $errors ? 'error' : 'success',
                'message' => $message,
                'details' => $details ?: null,
            ]);

            $keep = AutomationRun::where('automation_id', $automation->id)
                ->orderByDesc('id')->limit(100)->pluck('id');
            AutomationRun::where('automation_id', $automation->id)
                ->whereNotIn('id', $keep)->delete();
        } catch (\Throwable $e) {
            Log::error('[AUTOMATION] Failed to record run: '.$e->getMessage());
        }
    }

    /** Short human label for a list of action definitions, e.g. "moved card; added label". */
    private static function summariseActionTypes(array $actions): string
    {
        $names = [
            'move_card' => 'moved card',
            'add_label' => 'added label',
            'remove_due_date' => 'removed due date',
            'update_due_date' => 'updated due date',
            'mark_done' => 'marked done',
        ];

        $parts = array_map(fn ($a) => $names[$a['type'] ?? ''] ?? ($a['type'] ?? 'unknown'), $actions);

        return $parts ? implode('; ', $parts) : 'no actions';
    }

    private static function actionMoveCard(array $action, array $context): void
    {
        $targetColumnId = (int) $action['column_id'];
        $position = ($action['position'] ?? 'bottom') === 'top' ? 0 : static::getBottomPosition($targetColumnId);
        Task::where('id', $context['task_id'])->update(['column_id' => $targetColumnId, 'position' => $position]);
    }

    private static function actionAddLabel(array $action, array $context): void
    {
        $label = GlobalLabel::find($action['label_id'] ?? null);
        if (! $label) {
            return;
        }

        $exists = TaskLabel::where('task_id', $context['task_id'])
            ->where('global_label_id', $label->id)
            ->exists();

        if (! $exists) {
            TaskLabel::create([
                'task_id' => $context['task_id'],
                'label' => $label->name,
                'color' => $label->color,
                'global_label_id' => $label->id,
            ]);
        }
    }

    private static function actionUpdateDueDate(array $action, array $context): void
    {
        $days = (int) ($action['days'] ?? 0);
        $task = Task::find($context['task_id']);
        if ($task && $task->due_date) {
            $task->update(['due_date' => $task->due_date->addDays($days)]);
        }
    }

    private static function actionMarkDone(array $context): void
    {
        $task = Task::find($context['task_id']);
        if (! $task) {
            return;
        }

        $column = Column::find($task->column_id);
        if (! $column) {
            return;
        }

        $doneCol = Column::where('board_id', $column->board_id)
            ->whereRaw("LOWER(name) = 'done'")
            ->first();

        if ($doneCol) {
            $pos = static::getBottomPosition($doneCol->id);
            $task->update(['column_id' => $doneCol->id, 'position' => $pos]);
        }
    }

    private static function getBottomPosition(int $columnId): int
    {
        $max = Task::where('column_id', $columnId)->max('position');

        return $max !== null ? $max + 1 : 0;
    }

    // ---- Scheduled (time-based) actions ----------------------------------

    private static function boardColumnIds(Automation $automation): array
    {
        return Column::where('board_id', $automation->board_id)->pluck('id')->all();
    }

    private static function actionCreateCard(array $action, Automation $automation): string
    {
        $column = Column::find($action['column_id'] ?? null);
        if (! $column || $column->board_id !== $automation->board_id) {
            return 'Skipped create card (invalid column)';
        }

        $dueDate = null;
        if (isset($action['due_in_days']) && $action['due_in_days'] !== '' && $action['due_in_days'] !== null) {
            $dueDate = now()->addDays((int) $action['due_in_days']);
        }

        $task = Task::create([
            'column_id' => $column->id,
            'title' => $action['title'] ?? 'Untitled',
            'description' => $action['description'] ?? '',
            'priority' => $action['priority'] ?? 'none',
            'due_date' => $dueDate,
            'position' => static::getBottomPosition($column->id),
        ]);

        if (! empty($action['label_id'])) {
            static::actionAddLabel(['label_id' => $action['label_id']], ['task_id' => $task->id]);
        }

        return "Created card \"{$task->title}\" in {$column->name}";
    }

    private static function actionBulkMove(array $action, Automation $automation): string
    {
        $from = (int) ($action['from_column_id'] ?? 0);
        $to = (int) ($action['to_column_id'] ?? 0);
        $boardColumns = static::boardColumnIds($automation);

        if (! $from || ! $to || $from === $to
            || ! in_array($from, $boardColumns) || ! in_array($to, $boardColumns)) {
            return 'Skipped bulk move (invalid columns)';
        }

        $tasks = Task::where('column_id', $from)
            ->whereNull('archived_at')
            ->orderBy('position')
            ->get()
            ->values();

        if ($tasks->isEmpty()) {
            return 'Moved 0 cards';
        }

        if (($action['position'] ?? 'bottom') === 'top') {
            // Make room at the top of the target column, then place in order.
            Task::where('column_id', $to)->increment('position', $tasks->count());
            foreach ($tasks as $i => $task) {
                $task->update(['column_id' => $to, 'position' => $i]);
            }
        } else {
            $base = static::getBottomPosition($to);
            foreach ($tasks as $i => $task) {
                $task->update(['column_id' => $to, 'position' => $base + $i]);
            }
        }

        $toName = Column::find($to)?->name ?? "#{$to}";

        return "Moved {$tasks->count()} card(s) to {$toName}";
    }

    private static function actionDueCards(array $action, Automation $automation): string
    {
        $today = now(config('app.user_timezone'))->startOfDay();
        $scope = $action['scope'] ?? 'overdue';
        $query = Task::whereIn('column_id', static::boardColumnIds($automation))
            ->whereNull('archived_at')
            ->whereNotNull('due_date');

        match ($scope) {
            'due_today' => $query->whereDate('due_date', '=', $today->toDateString()),
            'due_within' => $query->whereDate('due_date', '>=', $today->toDateString())
                ->whereDate('due_date', '<=', $today->copy()->addDays((int) ($action['within_days'] ?? 0))->toDateString()),
            default => $query->whereDate('due_date', '<', $today->toDateString()), // overdue
        };

        $tasks = $query->get();
        foreach ($tasks as $task) {
            $ctx = ['task_id' => $task->id, 'column_id' => $task->column_id];
            match ($action['then'] ?? null) {
                'move' => static::actionMoveCard(
                    ['column_id' => $action['column_id'] ?? 0, 'position' => $action['position'] ?? 'bottom'],
                    $ctx
                ),
                'add_label' => static::actionAddLabel(['label_id' => $action['label_id'] ?? null], $ctx),
                'mark_done' => static::actionMarkDone($ctx),
                'archive' => $task->update(['archived_at' => now()]),
                default => null,
            };
        }

        $then = $action['then'] ?? 'none';

        return "Applied '{$then}' to {$tasks->count()} {$scope} card(s)";
    }

    private static function actionArchiveCards(array $action, Automation $automation): string
    {
        $scope = $action['scope'] ?? 'completed';
        $query = Task::whereIn('column_id', static::boardColumnIds($automation))
            ->whereNull('archived_at');

        match ($scope) {
            'column' => $query->where('column_id', (int) ($action['column_id'] ?? 0)),
            'done' => $query->whereIn('column_id', Column::where('board_id', $automation->board_id)
                ->whereRaw("LOWER(name) = 'done'")->pluck('id')->all() ?: [0]),
            default => $query->whereNotNull('completed_at'), // completed
        };

        $count = $query->count();
        $query->update(['archived_at' => now()]);

        return "Archived {$count} card(s) ({$scope})";
    }
}
