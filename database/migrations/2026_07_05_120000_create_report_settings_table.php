<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Global (single-user) configuration for the scheduled email report.
        // A single row is expected; ReportSetting::current() creates it lazily.
        Schema::create('report_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(false);
            // daily | weekdays | weekly
            $table->string('frequency')->default('daily');
            // ISO weekday 1..7 (only used when frequency = weekly)
            $table->unsignedTinyInteger('day_of_week')->nullable();
            // Local send time in the user timezone, "HH:MM".
            $table->string('time')->default('09:00');
            // Which report blocks to render, e.g. ["critical","columns","time","completed"].
            $table->json('sections')->default('["critical","columns","time","completed"]');
            // Falls back to the account email when null.
            $table->string('recipient_email')->nullable();
            // Last time the every-minute runner fired (UTC); guards double-sends.
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_settings');
    }
};
