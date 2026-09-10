<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('password');
            $table->timestamp('last_login_at')->nullable()->after('is_admin');
        });

        // Promote the first-registered user to admin so there is always at
        // least one account that can reach the admin area.
        $firstId = DB::table('users')->min('id');
        if ($firstId !== null) {
            DB::table('users')->where('id', $firstId)->update(['is_admin' => true]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_admin', 'last_login_at']);
        });
    }
};
