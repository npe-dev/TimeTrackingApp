<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Board extends Model
{
    protected $fillable = ['user_id', 'name', 'description', 'report_enabled'];

    protected $casts = [
        'report_enabled' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Per-user tenancy: only the owner sees their boards. Guarded by
        // Auth::check() so background jobs / console (report & automation
        // runners, seeders) with no authenticated user keep full access.
        static::addGlobalScope('owner', function (Builder $query) {
            if ($userId = Auth::id()) {
                $query->where('boards.user_id', $userId);
            }
        });

        // Stamp the creating user so new boards belong to them automatically.
        static::creating(function (Board $board) {
            if ($board->user_id === null && ($userId = Auth::id())) {
                $board->user_id = $userId;
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

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
