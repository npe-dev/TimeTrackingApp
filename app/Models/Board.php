<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Board extends Model
{
    protected $fillable = ['name', 'description'];

    public function columns()
    {
        return $this->hasMany(Column::class)->orderBy('position');
    }

    public function projects()
    {
        return $this->hasMany(Project::class)->orderBy('name');
    }

    public function automations()
    {
        return $this->hasMany(Automation::class);
    }

    public function labels()
    {
        return $this->hasMany(GlobalLabel::class)->orderBy('sort_order')->orderBy('id');
    }
}
