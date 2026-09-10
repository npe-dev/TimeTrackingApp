<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Project extends Model
{
    protected $fillable = ['user_id', 'board_id', 'name', 'color'];

    protected static function booted(): void
    {
        static::addGlobalScope('owner', function (Builder $query) {
            if ($userId = Auth::id()) {
                $query->where('projects.user_id', $userId);
            }
        });

        static::creating(function (Project $project) {
            if ($project->user_id === null && ($userId = Auth::id())) {
                $project->user_id = $userId;
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function board()
    {
        return $this->belongsTo(Board::class);
    }

    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}
