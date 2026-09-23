<?php

namespace App\Models;

use App\Support\Ownership;
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
        // Per-user tenancy: a user sees boards they own plus boards shared with
        // them (board_members). Guarded by Auth::check() so background jobs /
        // console (report & automation runners, seeders) with no authenticated
        // user keep full access.
        static::addGlobalScope('owner', function (Builder $query) {
            if ($userId = Auth::id()) {
                $query->whereIn('boards.id', Ownership::boardIds($userId));
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

    public function members()
    {
        return $this->hasMany(BoardMember::class);
    }

    public function memberUsers()
    {
        return $this->belongsToMany(User::class, 'board_members')->withPivot('role')->withTimestamps();
    }

    public function invitations()
    {
        return $this->hasMany(BoardInvitation::class);
    }

    /** True when the given user id is the board's owner (not merely a member). */
    public function isOwnedBy(?int $userId): bool
    {
        return $userId !== null && $this->user_id === $userId;
    }
}
