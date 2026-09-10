<?php

namespace App\Models;

use App\Support\Ownership;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Column extends Model
{
    protected $fillable = ['board_id', 'name', 'position'];

    protected static function booted(): void
    {
        static::addGlobalScope('owner', function (Builder $query) {
            if ($userId = Auth::id()) {
                $query->whereIn('columns.board_id', Ownership::boardIds($userId));
            }
        });
    }

    public function board()
    {
        return $this->belongsTo(Board::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class)->orderBy('position');
    }
}
