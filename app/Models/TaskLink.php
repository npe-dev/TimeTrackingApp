<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskLink extends Model
{
    protected $fillable = ['task_id', 'title', 'url', 'position'];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
