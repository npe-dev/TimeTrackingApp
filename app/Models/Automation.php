<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Automation extends Model
{
    protected $fillable = ['name', 'board_id', 'trigger_type', 'trigger_config', 'actions', 'enabled'];

    protected $casts = [
        'trigger_config' => 'array',
        'actions' => 'array',
        'enabled' => 'boolean',
    ];

    public function board()
    {
        return $this->belongsTo(Board::class);
    }
}
