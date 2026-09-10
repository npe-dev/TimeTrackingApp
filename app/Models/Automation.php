<?php

namespace App\Models;

use App\Support\Ownership;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Automation extends Model
{
    protected $fillable = ['name', 'board_id', 'trigger_type', 'trigger_config', 'actions', 'enabled', 'last_run_at'];

    protected $casts = [
        'trigger_config' => 'array',
        'actions' => 'array',
        'enabled' => 'boolean',
        'last_run_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('owner', function (Builder $query) {
            if ($userId = Auth::id()) {
                $query->whereIn('automations.board_id', Ownership::boardIds($userId));
            }
        });
    }

    public function board()
    {
        return $this->belongsTo(Board::class);
    }

    public function runs()
    {
        return $this->hasMany(AutomationRun::class)->latest();
    }
}
