<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalLabel extends Model
{
    protected $fillable = ['name', 'color', 'sort_order'];

    public function taskLabels()
    {
        return $this->hasMany(TaskLabel::class);
    }
}
