<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\Column;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskMoveTest extends TestCase
{
    use RefreshDatabase;

    private function order(int $columnId): array
    {
        return Task::where('column_id', $columnId)
            ->whereNull('parent_task_id')
            ->orderBy('position')->orderBy('id')
            ->pluck('title')->all();
    }

    public function test_dropping_at_the_end_with_gappy_positions_lands_last(): void
    {
        $user = User::factory()->create();
        $board = Board::create(['name' => 'Work']);
        $todo = Column::create(['board_id' => $board->id, 'name' => 'To Do', 'position' => 0]);
        $done = Column::create(['board_id' => $board->id, 'name' => 'Done', 'position' => 1]);

        // Non-contiguous positions, as produced by max+1 / archived churn.
        Task::create(['column_id' => $done->id, 'title' => 'A', 'position' => 0]);
        Task::create(['column_id' => $done->id, 'title' => 'B', 'position' => 5]);
        Task::create(['column_id' => $done->id, 'title' => 'C', 'position' => 8]);
        $mover = Task::create(['column_id' => $todo->id, 'title' => 'Mover', 'position' => 0]);

        // The board shows 3 cards in Done, so "drop at the end" is index 3.
        $this->actingAs($user)
            ->patchJson("/api/tasks/{$mover->id}/move", ['column_id' => $done->id, 'position' => 3])
            ->assertSuccessful();

        $this->assertSame(['A', 'B', 'C', 'Mover'], $this->order($done->id));
        $this->assertSame(3, $mover->refresh()->position);
    }

    public function test_dropping_in_the_middle_respects_the_index(): void
    {
        $user = User::factory()->create();
        $board = Board::create(['name' => 'Work']);
        $todo = Column::create(['board_id' => $board->id, 'name' => 'To Do', 'position' => 0]);
        $done = Column::create(['board_id' => $board->id, 'name' => 'Done', 'position' => 1]);

        Task::create(['column_id' => $done->id, 'title' => 'A', 'position' => 0]);
        Task::create(['column_id' => $done->id, 'title' => 'B', 'position' => 5]);
        Task::create(['column_id' => $done->id, 'title' => 'C', 'position' => 8]);
        $mover = Task::create(['column_id' => $todo->id, 'title' => 'Mover', 'position' => 0]);

        // Insert between A and B → index 1.
        $this->actingAs($user)
            ->patchJson("/api/tasks/{$mover->id}/move", ['column_id' => $done->id, 'position' => 1])
            ->assertSuccessful();

        $this->assertSame(['A', 'Mover', 'B', 'C'], $this->order($done->id));
    }

    public function test_same_column_reorder_to_end(): void
    {
        $user = User::factory()->create();
        $board = Board::create(['name' => 'Work']);
        $col = Column::create(['board_id' => $board->id, 'name' => 'To Do', 'position' => 0]);

        $a = Task::create(['column_id' => $col->id, 'title' => 'A', 'position' => 0]);
        Task::create(['column_id' => $col->id, 'title' => 'B', 'position' => 3]);
        Task::create(['column_id' => $col->id, 'title' => 'C', 'position' => 7]);

        // Move A to the end: among the other visible cards [B, C], index 2.
        $this->actingAs($user)
            ->patchJson("/api/tasks/{$a->id}/move", ['column_id' => $col->id, 'position' => 2])
            ->assertSuccessful();

        $this->assertSame(['B', 'C', 'A'], $this->order($col->id));
    }

    public function test_moving_out_compacts_the_source_column(): void
    {
        $user = User::factory()->create();
        $board = Board::create(['name' => 'Work']);
        $todo = Column::create(['board_id' => $board->id, 'name' => 'To Do', 'position' => 0]);
        $done = Column::create(['board_id' => $board->id, 'name' => 'Done', 'position' => 1]);

        $a = Task::create(['column_id' => $todo->id, 'title' => 'A', 'position' => 0]);
        $b = Task::create(['column_id' => $todo->id, 'title' => 'B', 'position' => 4]);
        $c = Task::create(['column_id' => $todo->id, 'title' => 'C', 'position' => 9]);

        $this->actingAs($user)
            ->patchJson("/api/tasks/{$a->id}/move", ['column_id' => $done->id, 'position' => 0])
            ->assertSuccessful();

        // Source column re-sequenced to a clean run.
        $this->assertSame(0, $b->refresh()->position);
        $this->assertSame(1, $c->refresh()->position);
        $this->assertSame(['B', 'C'], $this->order($todo->id));
    }
}
