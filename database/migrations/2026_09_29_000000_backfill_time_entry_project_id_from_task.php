<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Back-fill time entries whose project_id snapshot is null but whose task has
     * a project. These entries were created before their task was assigned a
     * project (or before that snapshot was kept in sync), so they showed blank in
     * the Times list. TaskController::update now keeps entries in sync going
     * forward; this fixes the existing rows.
     */
    public function up(): void
    {
        DB::table('time_entries')
            ->whereNull('project_id')
            ->whereNotNull('task_id')
            ->update([
                'project_id' => DB::raw('(SELECT project_id FROM tasks WHERE tasks.id = time_entries.task_id)'),
            ]);
    }

    /**
     * Irreversible: we can't tell which entries were null before the back-fill.
     */
    public function down(): void
    {
        // no-op
    }
};
