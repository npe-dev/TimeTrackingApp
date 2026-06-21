<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = ['board_id', 'name', 'color'];

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
