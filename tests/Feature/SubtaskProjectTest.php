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

class SubtaskProjectTest extends TestCase
{
    use RefreshDatabase;

    private function setUpBoard(): array
    {
        $user = User::factory()->create();
        $board = Board::create(['name' => 'Work', 'user_id' => $user->id]);
        // Alphabetically first, so it's what the board-default fallback would pick.
        $aaa = Project::create(['board_id' => $board->id, 'name' => 'Aaa', 'user_id' => $user->id]);
        $old = Project::create(['board_id' => $board->id, 'name' => 'Old', 'user_id' => $user->id]);
        $new = Project::create(['board_id' => $board->id, 'name' => 'New', 'user_id' => $user->id]);
        $column = Column::create(['board_id' => $board->id, 'name' => 'Todo', 'position' => 0, 'user_id' => $user->id]);
        $parent = Task::create(['column_id' => $column->id, 'project_id' => $old->id, 'title' => 'Parent', 'position' => 0, 'user_id' => $user->id]);
        $subtask = Task::create(['column_id' => $column->id, 'parent_task_id' => $parent->id, 'title' => 'Sub', 'position' => 0, 'user_id' => $user->id]);

        return compact('user', 'aaa', 'old', 'new', 'column', 'parent', 'subtask');
    }

    public function test_timer_on_subtask_without_project_uses_parent_project(): void
    {
        ['user' => $user, 'old' => $old, 'subtask' => $subtask] = $this->setUpBoard();

        $this->actingAs($user)
            ->postJson('/api/entries/start', ['task_id' => $subtask->id])
            ->assertSuccessful()
            ->assertJsonPath('project_id', $old->id);
    }

    public function test_changing_parent_project_repoints_inheriting_subtask_entries(): void
    {
        ['user' => $user, 'old' => $old, 'new' => $new, 'column' => $column, 'parent' => $parent, 'subtask' => $subtask] = $this->setUpBoard();

        $this->actingAs($user)->postJson('/api/entries/start', ['task_id' => $subtask->id])->assertSuccessful();

        $this->actingAs($user)->putJson("/api/tasks/{$parent->id}", [
            'column_id' => $column->id,
            'project_id' => $new->id,
            'title' => 'Parent',
            'priority' => 'none',
            'position' => 0,
        ])->assertSuccessful();

        $this->assertEquals($new->id, TimeEntry::where('task_id', $subtask->id)->value('project_id'));
        $this->actingAs($user)->getJson('/api/entries/running')->assertJsonPath('project_name', 'New');
    }

    public function test_subtask_with_own_project_is_not_repointed(): void
    {
        ['user' => $user, 'aaa' => $aaa, 'new' => $new, 'column' => $column, 'parent' => $parent, 'subtask' => $subtask] = $this->setUpBoard();
        $subtask->update(['project_id' => $aaa->id]);

        $this->actingAs($user)->postJson('/api/entries/start', ['task_id' => $subtask->id])->assertSuccessful();

        $this->actingAs($user)->putJson("/api/tasks/{$parent->id}", [
            'column_id' => $column->id,
            'project_id' => $new->id,
            'title' => 'Parent',
            'priority' => 'none',
            'position' => 0,
        ])->assertSuccessful();

        $this->assertEquals($aaa->id, TimeEntry::where('task_id', $subtask->id)->value('project_id'));
    }
}
