<?php
namespace App\Services;

use App\Models\Automation;
use App\Models\Column;
use App\Models\GlobalLabel;
use App\Models\Task;
use App\Models\TaskLabel;
use Illuminate\Support\Facades\Log;

class AutomationService
{
    public static function run(string $eventType, array $context): void
    {
        try {
            $column = Column::find($context['column_id'] ?? null);
            if (!$column) return;

            $automations = Automation::where('board_id', $column->board_id)
                ->where('enabled', true)
                ->get();

            foreach ($automations as $auto) {
                if (!static::matchesTrigger($auto->trigger_type, $auto->trigger_config, $eventType, $context)) {
                    continue;
                }

                Log::info("[AUTOMATION] Running \"{$auto->name}\" (id={$auto->id}) for task {$context['task_id']}");
                static::executeActions($auto->actions, $context);
            }
        } catch (\Throwable $e) {
            Log::error('[AUTOMATION] Error: ' . $e->getMessage());
        }
    }

    private static function matchesTrigger(string $triggerType, array $config, string $eventType, array $context): bool
    {
        return match ($triggerType) {
            'card_done' => $eventType === 'task_moved' && ($context['new_column_id'] ?? null) == ($config['done_column_id'] ?? null),
            'card_deleted' => $eventType === 'task_deleted',
            'card_created' => $eventType === 'task_created' && (!isset($config['column_id']) || $config['column_id'] == $context['column_id']),
            'card_added_to', 'card_moved_into' => $eventType === 'task_moved' && ($context['new_column_id'] ?? null) == ($config['column_id'] ?? null),
            'card_moved_out' => $eventType === 'task_moved' && ($context['old_column_id'] ?? null) == ($config['column_id'] ?? null),
            'due_date_set' => $eventType === 'task_updated' && ($context['due_date_changed'] ?? false) && !empty($context['new_due_date']),
            default => false,
        };
    }

    private static function executeActions(array $actions, array $context): void
    {
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
                Log::error("[AUTOMATION] Action error: " . $e->getMessage());
            }
        }
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
        if (!$label) return;

        $exists = TaskLabel::where('task_id', $context['task_id'])
            ->where('global_label_id', $label->id)
            ->exists();

        if (!$exists) {
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
        if (!$task) return;

        $column = Column::find($task->column_id);
        if (!$column) return;

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
}
