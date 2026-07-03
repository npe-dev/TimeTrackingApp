<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Automation extends Model
{
    protected $fillable = ['name', 'board_id', 'trigger_type', 'trigger_config', 'actions', 'enabled', 'last_run_at'];

    protected $casts = [
        'trigger_config' => 'array',
        'actions' => 'array',
        'enabled' => 'boolean',
        'last_run_at' => 'datetime',
    ];

    public function board()
    {
        return $this->belongsTo(Board::class);
    }

    public function runs()
    {
        return $this->hasMany(AutomationRun::class)->latest();
    }
}
