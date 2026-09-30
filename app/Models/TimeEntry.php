<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TimeEntry extends Model
{
    protected $fillable = ['project_id', 'task_id', 'user_id', 'description', 'start_time', 'end_time', 'last_heartbeat'];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'last_heartbeat' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Entries belonging to a board: via their project, or, for entries without a
     * project (card with no project, or project deleted), via their task's column.
     */
    public function scopeForBoard(Builder $query, int $boardId): Builder
    {
        return $query->where(function ($q) use ($boardId) {
            $q->whereHas('project', fn ($p) => $p->where('board_id', $boardId))
                ->orWhere(fn ($q) => $q->whereNull('project_id')
                    ->whereHas('task.column', fn ($c) => $c->where('board_id', $boardId)));
        });
    }

    /** The board this entry belongs to (see scopeForBoard). */
    public function boardId(): ?int
    {
        return $this->project?->board_id ?? $this->task?->column?->board_id;
    }
}
