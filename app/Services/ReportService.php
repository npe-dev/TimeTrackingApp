<?php

namespace App\Services;

use App\Mail\BoardReportMail;
use App\Models\Board;
use App\Models\ReportRun;
use App\Models\ReportSetting;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ReportService
{
    /**
     * Fire the scheduled report if the current minute matches the configured
     * cadence. Mirrors RunScheduledAutomations: every-minute runner, matched
     * against the clock in the user's timezone, guarded against double-sends.
     */
    public static function sendScheduled(): int
    {
        $settings = ReportSetting::current();

        if (! $settings->enabled) {
            return 0;
        }

        $tz = config('app.user_timezone');
        $now = now($tz);

        if (! static::scheduleMatches($settings, $now)) {
            return 0;
        }

        if (static::alreadyRanThisSlot($settings->last_run_at, $now, $tz)) {
            return 0;
        }

        $sent = 0;
        foreach (Board::where('report_enabled', true)->get() as $board) {
            if (static::sendForBoard($board, $settings)) {
                $sent++;
            }
        }

        $settings->forceFill(['last_run_at' => now()])->save();

        return $sent;
    }

    /**
     * Does the configured cadence fire at this exact minute (user timezone)?
     */
    public static function scheduleMatches(ReportSetting $settings, Carbon $now): bool
    {
        if ($now->format('H:i') !== ($settings->time ?: '09:00')) {
            return false;
        }

        return match ($settings->frequency) {
            'weekdays' => $now->isoWeekday() >= 1 && $now->isoWeekday() <= 5,
            'weekly' => (int) ($settings->day_of_week ?? 1) === $now->isoWeekday(),
            default => true, // daily
        };
    }

    /**
     * Guard against double-firing within the same minute slot, comparing in the
     * user's timezone (last_run_at is stored UTC).
     */
    private static function alreadyRanThisSlot(?Carbon $lastRunAt, Carbon $now, string $tz): bool
    {
        if (! $lastRunAt) {
            return false;
        }

        return $lastRunAt->copy()->setTimezone($tz)->format('Y-m-d H:i') === $now->format('Y-m-d H:i');
    }

    /**
     * Build + send the report for one board. Returns true if an email went out.
     * When $force is true (manual "send test now"), an email is sent even for an
     * empty board so the user can verify delivery.
     */
    public static function sendForBoard(Board $board, ?ReportSetting $settings = null, bool $force = false): bool
    {
        $settings ??= ReportSetting::current();
        $report = static::buildBoardReport($board, $settings);

        if (! $force && ! $report['has_any_tasks']) {
            static::record($board, 'skipped', 'Board has no tasks — nothing to report.');

            return false;
        }

        $recipient = $settings->recipient_email ?: optional(User::first())->email;

        if (! $recipient) {
            static::record($board, 'failed', 'No recipient email configured and no account email found.');

            return false;
        }

        try {
            Mail::to($recipient)->send(new BoardReportMail($report, $recipient));
            static::record($board, 'sent', "Report sent to {$recipient}.");

            return true;
        } catch (\Throwable $e) {
            Log::error("[REPORT] Failed to send board report (board={$board->id}): ".$e->getMessage());
            static::record($board, 'failed', $e->getMessage());

            return false;
        }
    }

    /**
     * Assemble every section's data for a board. "This week" = Mon–Sun in the
     * user's timezone. Only enabled sections are computed.
     */
    public static function buildBoardReport(Board $board, ?ReportSetting $settings = null): array
    {
        $settings ??= ReportSetting::current();
        $sections = $settings->sections ?: ReportSetting::ALL_SECTIONS;

        $tz = config('app.user_timezone');
        $now = now($tz);
        $startOfWeek = $now->copy()->startOfWeek();
        $endOfWeek = $now->copy()->endOfWeek();
        $today = $now->toDateString();

        $columns = $board->columns()->get();
        $columnIds = $columns->pluck('id');
        $taskIds = Task::whereIn('column_id', $columnIds)->pluck('id');
        $projectIds = $board->projects()->pluck('id');

        $report = [
            'board' => ['id' => $board->id, 'name' => $board->name],
            'app_name' => config('app.name'),
            'app_url' => config('app.url'),
            'generated_at' => $now->format('D, d M Y H:i'),
            'week_label' => $startOfWeek->format('d M').' – '.$endOfWeek->format('d M Y'),
            'sections' => $sections,
            'has_any_tasks' => $taskIds->isNotEmpty(),
            'critical' => [],
            'overdue' => [],
            'columns' => [],
            'time' => ['total_seconds' => 0, 'projects' => []],
            'completed' => [],
        ];

        // ── Critical: due this week (not done, not archived) ──────────────
        if (in_array('critical', $sections, true)) {
            $due = Task::with(['column', 'project', 'labels'])
                ->whereIn('column_id', $columnIds)
                ->whereNull('archived_at')
                ->whereNull('completed_at')
                ->whereNotNull('due_date')
                ->whereDate('due_date', '>=', $startOfWeek->toDateString())
                ->whereDate('due_date', '<=', $endOfWeek->toDateString())
                ->orderBy('due_date')
                ->get();

            $report['critical'] = $due->map(fn (Task $t) => static::formatTask($t))->all();

            // Overdue rides along with the "critical" section.
            $overdue = Task::with(['column', 'project', 'labels'])
                ->whereIn('column_id', $columnIds)
                ->whereNull('archived_at')
                ->whereNull('completed_at')
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', $today)
                ->orderBy('due_date')
                ->get();

            $report['overdue'] = $overdue->map(fn (Task $t) => static::formatTask($t))->all();
        }

        // ── Summary: task count per column ────────────────────────────────
        if (in_array('columns', $sections, true)) {
            $report['columns'] = $columns->map(fn ($c) => [
                'name' => $c->name,
                'count' => Task::where('column_id', $c->id)->whereNull('archived_at')->count(),
            ])->all();
        }

        // ── Time tracked this week (per project) ──────────────────────────
        if (in_array('time', $sections, true)) {
            $entries = TimeEntry::with('project')
                ->where('start_time', '>=', $startOfWeek->copy()->utc())
                ->where('start_time', '<=', $endOfWeek->copy()->utc())
                ->where(function ($q) use ($projectIds, $taskIds) {
                    $q->whereIn('project_id', $projectIds)
                        ->orWhereIn('task_id', $taskIds);
                })
                ->get();

            $byProject = [];
            $total = 0;
            foreach ($entries as $entry) {
                $end = $entry->end_time ?? now();
                $seconds = max(0, $end->getTimestamp() - $entry->start_time->getTimestamp());
                $total += $seconds;

                $key = $entry->project?->id ?? 0;
                if (! isset($byProject[$key])) {
                    $byProject[$key] = [
                        'name' => $entry->project?->name ?? 'No project',
                        'color' => $entry->project?->color ?? '#9ca3af',
                        'seconds' => 0,
                    ];
                }
                $byProject[$key]['seconds'] += $seconds;
            }

            usort($byProject, fn ($a, $b) => $b['seconds'] <=> $a['seconds']);
            $report['time'] = [
                'total_seconds' => $total,
                'total_label' => static::formatDuration($total),
                'projects' => array_map(fn ($p) => [
                    ...$p,
                    'label' => static::formatDuration($p['seconds']),
                ], $byProject),
            ];
        }

        // ── Completed this week ───────────────────────────────────────────
        if (in_array('completed', $sections, true)) {
            $completed = Task::with(['column', 'project'])
                ->whereIn('column_id', $columnIds)
                ->whereNull('archived_at')
                ->whereNotNull('completed_at')
                ->where('completed_at', '>=', $startOfWeek->copy()->utc())
                ->where('completed_at', '<=', $endOfWeek->copy()->utc())
                ->orderByDesc('completed_at')
                ->get();

            $report['completed'] = $completed->map(fn (Task $t) => static::formatTask($t))->all();
        }

        return $report;
    }

    /** Compact task shape used across all sections. */
    private static function formatTask(Task $task): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'priority' => $task->priority ?: 'none',
            'due_date' => $task->due_date?->format('D, d M'),
            'column' => $task->column?->name,
            'project' => $task->project?->name,
            'project_color' => $task->project?->color,
            'labels' => $task->relationLoaded('labels')
                ? $task->labels->map(fn ($l) => ['label' => $l->label, 'color' => $l->color])->all()
                : [],
        ];
    }

    /** Seconds → "Xh Ym" (or "Ym" under an hour, "0m" when empty). */
    public static function formatDuration(int $seconds): string
    {
        $minutes = intdiv($seconds, 60);
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        if ($h > 0) {
            return "{$h}h {$m}m";
        }

        return "{$m}m";
    }

    /** Record a delivery attempt, keeping only the most recent 100 rows. */
    private static function record(Board $board, string $status, ?string $message): void
    {
        ReportRun::create([
            'board_id' => $board->id,
            'status' => $status,
            'message' => $message,
            'sent_at' => $status === 'sent' ? now() : null,
        ]);

        $keepFrom = ReportRun::orderByDesc('id')->skip(100)->take(1)->value('id');
        if ($keepFrom) {
            ReportRun::where('id', '<=', $keepFrom)->delete();
        }
    }
}
