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
     * Start a timer for the given user. A timer always belongs to a board via its
     * project: when starting from a task without an explicit project, fall back to
     * the task's own project or the task's board default project ("General").
     */
    public static function start(User $user, ?int $projectId, ?int $taskId, string $description = ''): TimeEntry
    {
        if (! $projectId && $taskId) {
            $task = Task::with('column.board')->find($taskId);
            $projectId = $task?->project_id
                ?? $task?->column?->board?->projects()->orderBy('id')->value('id');
        }

        if (! $projectId) {
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
        return TimeEntry::with(['project', 'task'])
            ->where('user_id', $user->id)
            ->whereNull('end_time')
            ->orderByDesc('start_time')
            ->first();
    }
}
