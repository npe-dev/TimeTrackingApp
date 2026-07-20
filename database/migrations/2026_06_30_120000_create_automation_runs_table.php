<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('success'); // success | error
            $table->string('message');
            $table->json('details')->nullable();
            $table->timestamps();

            $table->index(['automation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_runs');
    }
};
