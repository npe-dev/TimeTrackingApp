<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per-board opt-in for the scheduled email report.
        Schema::table('boards', function (Blueprint $table) {
            $table->boolean('report_enabled')->default(true)->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('boards', function (Blueprint $table) {
            $table->dropColumn('report_enabled');
        });
    }
};
