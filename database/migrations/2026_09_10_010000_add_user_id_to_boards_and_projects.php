<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Introduces per-user ownership. Boards are the tenancy root; projects also
     * carry user_id directly since a project can exist without a board.
     * All pre-existing data is backfilled to the first admin (lowest user id),
     * matching the earlier admin-promotion migration.
     */
    public function up(): void
    {
        Schema::table('boards', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        $firstUserId = DB::table('users')->min('id');
        if ($firstUserId !== null) {
            DB::table('boards')->whereNull('user_id')->update(['user_id' => $firstUserId]);
            DB::table('projects')->whereNull('user_id')->update(['user_id' => $firstUserId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('boards', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
