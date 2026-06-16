<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\GlobalLabel;

class Board extends Model
{
    protected $fillable = ['name', 'description'];

    public function columns()
    {
        return $this->hasMany(Column::class)->orderBy('position');
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
