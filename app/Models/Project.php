<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = ['name', 'color'];

    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}
