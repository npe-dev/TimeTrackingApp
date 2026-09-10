<?php

namespace App\Models;

use App\Support\Ownership;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ChecklistItem extends Model
{
    protected $fillable = ['task_id', 'title', 'completed', 'position'];

    protected $casts = [
        'completed' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('owner', function (Builder $query) {
            if ($userId = Auth::id()) {
                $query->whereIn('checklist_items.task_id', Ownership::taskIds($userId));
            }
        });
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
