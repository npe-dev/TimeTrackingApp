<?php

namespace App\Models;

use App\Support\Ownership;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Project extends Model
{
    protected $fillable = ['user_id', 'board_id', 'name', 'color'];

    protected static function booted(): void
    {
        // Members of a shared board must see that board's projects, so scope by
        // accessible board (owned ∪ shared). Keep matching the user's own
        // projects too, so a project with a null board_id (legacy/global) stays
        // visible to its owner.
        static::addGlobalScope('owner', function (Builder $query) {
            if ($userId = Auth::id()) {
                $query->where(function (Builder $q) use ($userId) {
                    $q->whereIn('projects.board_id', Ownership::boardIds($userId))
                        ->orWhere('projects.user_id', $userId);
                });
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
