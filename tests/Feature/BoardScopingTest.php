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

class BoardScopingTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_board_auto_creates_a_default_project(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/boards', ['name' => 'Work'])
            ->assertSuccessful();

        $board = Board::where('name', 'Work')->first();
        $this->assertNotNull($board);
        $this->assertEquals(1, $board->projects()->count());
        $this->assertEquals('General', $board->projects()->first()->name);
    }

    public function test_projects_are_scoped_by_board(): void
    {
        $user = User::factory()->create();
        $boardA = Board::create(['name' => 'A']);
        $boardB = Board::create(['name' => 'B']);
        Project::create(['board_id' => $boardA->id, 'name' => 'Alpha']);
        Project::create(['board_id' => $boardB->id, 'name' => 'Beta']);

        $response = $this->actingAs($user)
            ->getJson("/api/projects?board_id={$boardA->id}")
            ->assertSuccessful();

        $names = collect($response->json())->pluck('name');
        $this->assertContains('Alpha', $names);
        $this->assertNotContains('Beta', $names);
    }

    public function test_starting_a_timer_without_a_project_or_task_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/entries/start', [])
            ->assertStatus(422);
    }

    public function test_starting_a_timer_with_a_project_succeeds(): void
    {
        $user = User::factory()->create();
        $board = Board::create(['name' => 'Work']);
        $project = Project::create(['board_id' => $board->id, 'name' => 'Alpha']);

        $this->actingAs($user)
            ->postJson('/api/entries/start', ['project_id' => $project->id])
            ->assertSuccessful()
            ->assertJsonPath('project_id', $project->id);

        $this->assertNotNull(TimeEntry::where('user_id', $user->id)->whereNull('end_time')->first());
    }

    public function test_task_timer_without_project_falls_back_to_board_default(): void
    {
        $user = User::factory()->create();
        $board = Board::create(['name' => 'Work']);
        $project = Project::create(['board_id' => $board->id, 'name' => 'General']);
        $column = Column::create(['board_id' => $board->id, 'name' => 'To Do', 'position' => 0]);
        $task = Task::create(['column_id' => $column->id, 'title' => 'Do it', 'position' => 0]);

        $this->actingAs($user)
            ->postJson('/api/entries/start', ['task_id' => $task->id])
            ->assertSuccessful()
            ->assertJsonPath('project_id', $project->id);
    }

    public function test_report_summary_is_scoped_by_board(): void
    {
        $user = User::factory()->create();
        $boardA = Board::create(['name' => 'A']);
        $boardB = Board::create(['name' => 'B']);
        $projectA = Project::create(['board_id' => $boardA->id, 'name' => 'Alpha']);
        $projectB = Project::create(['board_id' => $boardB->id, 'name' => 'Beta']);

        TimeEntry::create([
            'user_id' => $user->id,
            'project_id' => $projectA->id,
            'start_time' => now()->subHour(),
            'end_time' => now(),
        ]);
        TimeEntry::create([
            'user_id' => $user->id,
            'project_id' => $projectB->id,
            'start_time' => now()->subHour(),
            'end_time' => now(),
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/reports/summary?board_id={$boardA->id}")
            ->assertSuccessful();

        $names = collect($response->json('byProject'))->pluck('name');
        $this->assertContains('Alpha', $names);
        $this->assertNotContains('Beta', $names);
    }
}
