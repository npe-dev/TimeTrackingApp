<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskLabel extends Model
{
    protected $fillable = ['task_id', 'label', 'color', 'global_label_id'];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function globalLabel()
    {
        return $this->belongsTo(GlobalLabel::class);
    }
}
