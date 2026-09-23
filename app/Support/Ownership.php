<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\DB;

/**
 * Builds "accessible id" sub-queries used by model global scopes to enforce
 * per-user tenancy. Access is rooted at boards; every other resource is reached
 * through its board (board → column → task). A user can access a board they own
 * (boards.user_id) or one shared with them (board_members).
 *
 * These return Closures suitable for passing to the query builder's
 * whereIn($column, Closure) form, so they compose without loading models.
 */
class Ownership
{
    /** Board ids the given user can access: owned ∪ shared-with (member). */
    public static function boardIds(int $userId): Closure
    {
        return function ($q) use ($userId) {
            $q->select('id')->from('boards')->where('user_id', $userId)
                ->union(
                    DB::table('board_members')->select('board_id')->where('user_id', $userId)
                );
        };
    }

    /** Column ids on boards owned by the given user. */
    public static function columnIds(int $userId): Closure
    {
        return fn ($q) => $q->select('id')->from('columns')->whereIn('board_id', self::boardIds($userId));
    }

    /** Task ids in columns on boards owned by the given user. */
    public static function taskIds(int $userId): Closure
    {
        return fn ($q) => $q->select('id')->from('tasks')->whereIn('column_id', self::columnIds($userId));
    }
}
