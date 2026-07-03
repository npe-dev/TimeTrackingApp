<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = ['column_id', 'project_id', 'parent_task_id', 'title', 'description', 'due_date', 'priority', 'position', 'completed_at', 'archived_at'];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    public function column()
    {
        return $this->belongsTo(Column::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function labels()
    {
        return $this->hasMany(TaskLabel::class);
    }

    public function checklistItems()
    {
        return $this->hasMany(ChecklistItem::class)->orderBy('position');
    }

    public function links()
    {
        return $this->hasMany(TaskLink::class)->orderBy('position');
    }

    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function parentTask()
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    public function subtasks()
    {
        return $this->hasMany(Task::class, 'parent_task_id')->orderBy('position');
    }
}
