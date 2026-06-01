<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = ['column_id', 'project_id', 'title', 'description', 'due_date', 'priority', 'position'];

    protected $casts = [
        'due_date' => 'date',
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
}
