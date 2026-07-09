<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportRun extends Model
{
    protected $fillable = [
        'board_id',
        'status',
        'message',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function board()
    {
        return $this->belongsTo(Board::class);
    }
}
