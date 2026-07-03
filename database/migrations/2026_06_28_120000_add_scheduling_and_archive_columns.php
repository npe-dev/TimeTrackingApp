<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tracks the last time a scheduled automation fired, so the every-minute
        // runner only triggers once per scheduled slot (stored in UTC).
        Schema::table('automations', function (Blueprint $table) {
            $table->timestamp('last_run_at')->nullable()->after('enabled');
        });

        // Soft-archive for tasks. Archived tasks are hidden from the board but
        // not deleted (stored in UTC).
        Schema::table('tasks', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('automations', function (Blueprint $table) {
            $table->dropColumn('last_run_at');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('archived_at');
        });
    }
};
