<?php

use App\Models\Board;
use App\Models\Project;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('board_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        // Backfill: existing projects are global — assign them all to the first board.
        $firstBoard = Board::orderBy('id')->first();
        if ($firstBoard) {
            Project::whereNull('board_id')->update(['board_id' => $firstBoard->id]);
        }

        // Every board needs at least one project so a timer can always be started.
        foreach (Board::doesntHave('projects')->get() as $board) {
            Project::create([
                'board_id' => $board->id,
                'name' => 'General',
                'color' => '#3B82F6',
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['board_id']);
            $table->dropColumn('board_id');
        });
    }
};
