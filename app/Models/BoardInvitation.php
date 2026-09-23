<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoardInvitation extends Model
{
    protected $fillable = ['board_id', 'email', 'token', 'role', 'invited_by', 'accepted_at', 'expires_at'];

    protected $casts = [
        'accepted_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function board()
    {
        return $this->belongsTo(Board::class);
    }

    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
