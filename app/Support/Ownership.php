<?php

namespace App\Support;

use Closure;

/**
 * Builds "owned id" sub-queries used by model global scopes to enforce
 * per-user tenancy. Ownership is rooted at boards.user_id; every other
 * resource is reached through its board (board → column → task).
 *
 * These return Closures suitable for passing to the query builder's
 * whereIn($column, Closure) form, so they compose without loading models.
 */
class Ownership
{
    /** Board ids owned by the given user. */
    public static function boardIds(int $userId): Closure
    {
        return fn ($q) => $q->select('id')->from('boards')->where('user_id', $userId);
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
