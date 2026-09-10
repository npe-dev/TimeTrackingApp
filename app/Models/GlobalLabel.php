<?php

namespace App\Models;

use App\Support\Ownership;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class GlobalLabel extends Model
{
    protected $fillable = ['board_id', 'name', 'color', 'sort_order'];

    protected static function booted(): void
    {
        static::addGlobalScope('owner', function (Builder $query) {
            if ($userId = Auth::id()) {
                $query->whereIn('global_labels.board_id', Ownership::boardIds($userId));
            }
        });
    }

    public function taskLabels()
    {
        return $this->hasMany(TaskLabel::class);
    }
}
