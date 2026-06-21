<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\Column;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_card_csv_export_includes_subtask_time_entries(): void
    {
        $user = User::factory()->create();
        $board = Board::create(['name' => 'Work']);
        $project = Project::create(['board_id' => $board->id, 'name' => 'Alpha']);
        $column = Column::create(['board_id' => $board->id, 'name' => 'To Do', 'position' => 0]);

        $parent = Task::create(['column_id' => $column->id, 'title' => 'Parent', 'position' => 0]);
        $subtask = Task::create([
            'column_id' => $column->id,
            'parent_task_id' => $parent->id,
            'title' => 'Child',
            'position' => 0,
        ]);

        TimeEntry::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'task_id' => $parent->id,
            'description' => 'parent work',
            'start_time' => now()->subHours(2),
            'end_time' => now()->subHour(),
        ]);
        TimeEntry::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'task_id' => $subtask->id,
            'description' => 'subtask work',
            'start_time' => now()->subHours(4),
            'end_time' => now()->subHours(3),
        ]);

        $response = $this->actingAs($user)
            ->get("/api/entries/export/csv?task_id={$parent->id}")
            ->assertSuccessful();

        $csv = $response->getContent();
        $this->assertStringContainsString('parent work', $csv);
        $this->assertStringContainsString('subtask work', $csv);
    }
}
