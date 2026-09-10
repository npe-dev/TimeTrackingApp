<?php

namespace App\Models;

use App\Support\Ownership;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class TaskLabel extends Model
{
    protected $fillable = ['task_id', 'label', 'color', 'global_label_id'];

    protected static function booted(): void
    {
        static::addGlobalScope('owner', function (Builder $query) {
            if ($userId = Auth::id()) {
                $query->whereIn('task_labels.task_id', Ownership::taskIds($userId));
            }
        });
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function globalLabel()
    {
        return $this->belongsTo(GlobalLabel::class);
    }
}
