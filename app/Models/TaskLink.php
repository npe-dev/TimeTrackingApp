<?php

namespace App\Models;

use App\Support\Ownership;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class TaskLink extends Model
{
    protected $fillable = ['task_id', 'title', 'url', 'position'];

    protected static function booted(): void
    {
        static::addGlobalScope('owner', function (Builder $query) {
            if ($userId = Auth::id()) {
                $query->whereIn('task_links.task_id', Ownership::taskIds($userId));
            }
        });
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
