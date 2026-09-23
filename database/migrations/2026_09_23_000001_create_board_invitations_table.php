<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pending invitations to a shared board. An invite is addressed to an email
     * and carries a random token used in the accept link. It is consumed when
     * the matching (by email) logged-in user accepts, creating a board_members row.
     */
    public function up(): void
    {
        Schema::create('board_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->constrained()->cascadeOnDelete();
            $table->string('email')->index();
            $table->string('token')->unique();
            $table->string('role')->default('member');
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_invitations');
    }
};
