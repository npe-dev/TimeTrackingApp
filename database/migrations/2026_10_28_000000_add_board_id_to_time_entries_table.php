<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Board an entry belonged to, stamped when its card is deleted so entries with
     * no project stay on their board's Times list and reports (see TimeEntry::scopeForBoard).
     */
    public function up(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->foreignId('board_id')->nullable()->after('task_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('board_id');
        });
    }
};
