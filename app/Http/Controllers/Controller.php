<?php

namespace App\Http\Controllers;

use App\Models\Board;
use Illuminate\Support\Facades\Auth;

abstract class Controller
{
    /**
     * Abort with 403 unless the current user owns the board. Members of a shared
     * board can view/work it (via the expanded ownership scopes) but only the
     * owner may manage board settings, projects, labels, reports and automations.
     */
    protected function ensureBoardOwner(Board $board): void
    {
        abort_unless($board->isOwnedBy(Auth::id()), 403, 'Only the board owner can do this.');
    }
}
