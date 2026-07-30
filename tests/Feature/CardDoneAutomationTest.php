<?php

namespace Tests\Feature;

use App\Models\Automation;
use App\Models\Board;
use App\Models\Column;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CardDoneAutomationTest extends TestCase
{
    use RefreshDatabase;

    public function test_marking_a_card_done_runs_a_card_done_automation(): void
    {
        $user = User::factory()->create();
        $board = Board::create(['name' => 'Work']);
        $todo = Column::create(['board_id' => $board->id, 'name' => 'To Do', 'position' => 0]);
        $done = Column::create(['board_id' => $board->id, 'name' => 'Done', 'position' => 1]);
        $task = Task::create(['column_id' => $todo->id, 'title' => 'Ship it', 'position' => 0]);

        Automation::create([
            'name' => 'Move done cards',
            'board_id' => $board->id,
            'trigger_type' => 'card_done',
            'trigger_config' => [],
            'actions' => [['type' => 'move_card', 'column_id' => $done->id, 'position' => 'bottom']],
            'enabled' => true,
        ]);

        $this->actingAs($user)
            ->patchJson("/api/tasks/{$task->id}/toggle-complete")
            ->assertSuccessful();

        $this->assertEquals($done->id, $task->refresh()->column_id);
        $this->assertNotNull($task->completed_at);
    }

    public function test_move_to_top_lands_the_card_at_position_zero_and_shifts_the_rest(): void
    {
        $user = User::factory()->create();
        $board = Board::create(['name' => 'Work']);
        $todo = Column::create(['board_id' => $board->id, 'name' => 'To Do', 'position' => 0]);
        $done = Column::create(['board_id' => $board->id, 'name' => 'Done', 'position' => 1]);

        // Done already has two cards occupying positions 0 and 1.
        $existingA = Task::create(['column_id' => $done->id, 'title' => 'A', 'position' => 0]);
        $existingB = Task::create(['column_id' => $done->id, 'title' => 'B', 'position' => 1]);
        $task = Task::create(['column_id' => $todo->id, 'title' => 'Ship it', 'position' => 0]);

        Automation::create([
            'name' => 'Move done cards to top',
            'board_id' => $board->id,
            'trigger_type' => 'card_done',
            'trigger_config' => [],
            'actions' => [['type' => 'move_card', 'column_id' => $done->id, 'position' => 'top']],
            'enabled' => true,
        ]);

        $this->actingAs($user)
            ->patchJson("/api/tasks/{$task->id}/toggle-complete")
            ->assertSuccessful();

        // The completed card is strictly first; the previous occupants shift down.
        $this->assertEquals($done->id, $task->refresh()->column_id);
        $this->assertEquals(0, $task->position);
        $this->assertEquals(1, $existingA->refresh()->position);
        $this->assertEquals(2, $existingB->refresh()->position);
    }

    public function test_unchecking_a_done_card_does_not_run_the_automation(): void
    {
        $user = User::factory()->create();
        $board = Board::create(['name' => 'Work']);
        $todo = Column::create(['board_id' => $board->id, 'name' => 'To Do', 'position' => 0]);
        $done = Column::create(['board_id' => $board->id, 'name' => 'Done', 'position' => 1]);
        $task = Task::create([
            'column_id' => $todo->id,
            'title' => 'Already done',
            'position' => 0,
            'completed_at' => now(),
        ]);

        Automation::create([
            'name' => 'Move done cards',
            'board_id' => $board->id,
            'trigger_type' => 'card_done',
            'trigger_config' => [],
            'actions' => [['type' => 'move_card', 'column_id' => $done->id, 'position' => 'bottom']],
            'enabled' => true,
        ]);

        $this->actingAs($user)
            ->patchJson("/api/tasks/{$task->id}/toggle-complete")
            ->assertSuccessful();

        // Un-completing should not move the card.
        $this->assertEquals($todo->id, $task->refresh()->column_id);
        $this->assertNull($task->completed_at);
    }
}
