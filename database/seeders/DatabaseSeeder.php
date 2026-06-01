<?php

namespace Database\Seeders;

use App\Models\Board;
use App\Models\Column;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        Project::create([
            'name' => 'Default Project',
            'color' => '#3B82F6',
        ]);

        $board = Board::create([
            'name' => 'My Board',
            'description' => '',
        ]);

        Column::insert([
            ['board_id' => $board->id, 'name' => 'To Do', 'position' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['board_id' => $board->id, 'name' => 'In Progress', 'position' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['board_id' => $board->id, 'name' => 'Done', 'position' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
