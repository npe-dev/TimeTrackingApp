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
        $boardA = Board::create(['name' => 'A', 'user_id' => $user->id]);
        $boardB = Board::create(['name' => 'B', 'user_id' => $user->id]);
        Project::create(['board_id' => $boardA->id, 'name' => 'Alpha', 'user_id' => $user->id]);
        Project::create(['board_id' => $boardB->id, 'name' => 'Beta', 'user_id' => $user->id]);

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
        $board = Board::create(['name' => 'Work', 'user_id' => $user->id]);
        $project = Project::create(['board_id' => $board->id, 'name' => 'Alpha', 'user_id' => $user->id]);

        $this->actingAs($user)
            ->postJson('/api/entries/start', ['project_id' => $project->id])
            ->assertSuccessful()
            ->assertJsonPath('project_id', $project->id)
            // The frontend uses board_id to only show the timer on its own board.
            ->assertJsonPath('board_id', $board->id);

        $this->assertNotNull(TimeEntry::where('user_id', $user->id)->whereNull('end_time')->first());
    }

    public function test_running_entry_payload_includes_board_id(): void
    {
        $user = User::factory()->create();
        $board = Board::create(['name' => 'Work', 'user_id' => $user->id]);
        $project = Project::create(['board_id' => $board->id, 'name' => 'Alpha', 'user_id' => $user->id]);
        TimeEntry::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'start_time' => now()->subMinutes(5),
        ]);

        $this->actingAs($user)
            ->getJson('/api/entries/running')
            ->assertSuccessful()
            ->assertJsonPath('board_id', $board->id);
    }

    public function test_task_timer_without_project_falls_back_to_board_default(): void
    {
        $user = User::factory()->create();
        $board = Board::create(['name' => 'Work', 'user_id' => $user->id]);
        $project = Project::create(['board_id' => $board->id, 'name' => 'General', 'user_id' => $user->id]);
        $column = Column::create(['board_id' => $board->id, 'name' => 'To Do', 'position' => 0]);
        $task = Task::create(['column_id' => $column->id, 'title' => 'Do it', 'position' => 0]);

        $this->actingAs($user)
            ->postJson('/api/entries/start', ['task_id' => $task->id])
            ->assertSuccessful()
            ->assertJsonPath('project_id', $project->id);
    }

    public function test_orphaned_entry_still_appears_under_its_tasks_board(): void
    {
        // Reproduces the prod case: a project deletion nulls the entry's
        // project_id (nullOnDelete) but the task keeps its project link. The
        // board-filtered Timer list must still surface the entry, falling back
        // to the task's project for the board_id / project name.
        $user = User::factory()->create();
        $board = Board::create(['name' => 'Work', 'user_id' => $user->id]);
        $project = Project::create(['board_id' => $board->id, 'name' => 'Datalake', 'user_id' => $user->id]);
        $column = Column::create(['board_id' => $board->id, 'name' => 'To Do', 'position' => 0]);
        $task = Task::create(['column_id' => $column->id, 'project_id' => $project->id, 'title' => 'Restore project', 'position' => 0]);

        TimeEntry::create([
            'user_id' => $user->id,
            'project_id' => null, // orphaned by a project deletion
            'task_id' => $task->id,
            'start_time' => now()->subHour(),
            'end_time' => now(),
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/entries?board_id={$board->id}")
            ->assertSuccessful();

        $this->assertCount(1, $response->json());
        $this->assertEquals($board->id, $response->json('0.board_id'));
        $this->assertEquals('Datalake', $response->json('0.project_name'));
    }

    public function test_a_user_cannot_see_or_reach_another_users_boards_and_tasks(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $board = Board::create(['name' => 'Private', 'user_id' => $owner->id]);
        $column = Column::create(['board_id' => $board->id, 'name' => 'To Do', 'position' => 0]);
        $task = Task::create(['column_id' => $column->id, 'title' => 'Secret', 'position' => 0]);

        // The other user's board list is empty and never includes the owner's board.
        $boards = $this->actingAs($other)->getJson('/api/boards')->assertSuccessful()->json();
        $this->assertCount(0, $boards);

        // Direct-by-id access to the owner's resources 404s for the other user.
        $this->actingAs($other)->getJson("/api/boards/{$board->id}")->assertNotFound();
        $this->actingAs($other)->getJson("/api/columns/{$column->id}/tasks")
            ->assertSuccessful()->assertExactJson([]);
        $this->actingAs($other)->getJson("/api/tasks/{$task->id}")->assertNotFound();

        // The other user also cannot create a task under the owner's column.
        $this->actingAs($other)
            ->postJson('/api/tasks', ['column_id' => $column->id, 'title' => 'Intruder'])
            ->assertNotFound();

        // The owner still sees their own board and task.
        $this->actingAs($owner)->getJson('/api/boards')->assertSuccessful()->assertJsonCount(1);
        $this->actingAs($owner)->getJson("/api/tasks/{$task->id}")
            ->assertSuccessful()->assertJsonPath('title', 'Secret');
    }

    public function test_report_summary_is_scoped_by_board(): void
    {
        $user = User::factory()->create();
        $boardA = Board::create(['name' => 'A', 'user_id' => $user->id]);
        $boardB = Board::create(['name' => 'B', 'user_id' => $user->id]);
        $projectA = Project::create(['board_id' => $boardA->id, 'name' => 'Alpha', 'user_id' => $user->id]);
        $projectB = Project::create(['board_id' => $boardB->id, 'name' => 'Beta', 'user_id' => $user->id]);

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

        $names = collect($response->json('by_project'))->pluck('name');
        $this->assertContains('Alpha', $names);
        $this->assertNotContains('Beta', $names);
    }
}
