<?php

namespace App\Models;

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
}
