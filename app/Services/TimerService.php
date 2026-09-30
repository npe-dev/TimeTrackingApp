<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class TimerService
{
    /**
     * Start a timer for the given user. When starting from a task without an
     * explicit project, use the task's own project, then its parent's (subtasks
     * inherit the parent's project unless they set their own). A card with no
     * project logs with no project; the entry still belongs to the card's board
     * through its column (see TimeEntry::scopeForBoard).
     */
    public static function start(User $user, ?int $projectId, ?int $taskId, string $description = ''): TimeEntry
    {
        if (! $projectId && $taskId) {
            $task = Task::with('parentTask')->find($taskId);
            $projectId = $task?->project_id ?? $task?->parentTask?->project_id;
        }

        // Without a task there's nothing to tie the entry to a board.
        if (! $projectId && ! $taskId) {
            throw ValidationException::withMessages([
                'project_id' => ['A project is required to start a timer.'],
            ]);
        }

        $now = Carbon::now();

        // Stop any running timer first.
        TimeEntry::where('user_id', $user->id)
            ->whereNull('end_time')
            ->update(['end_time' => $now]);

        return TimeEntry::create([
            'project_id' => $projectId,
            'task_id' => $taskId,
            'description' => $description,
            'start_time' => $now,
            'last_heartbeat' => $now,
            'user_id' => $user->id,
        ]);
    }

    public static function stop(User $user): void
    {
        TimeEntry::where('user_id', $user->id)
            ->whereNull('end_time')
            ->update(['end_time' => Carbon::now()]);
    }

    public static function running(User $user): ?TimeEntry
    {
        return TimeEntry::with(['project', 'task.column'])
            ->where('user_id', $user->id)
            ->whereNull('end_time')
            ->orderByDesc('start_time')
            ->first();
    }
}
