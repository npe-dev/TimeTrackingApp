<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\Column;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskDeleteKeepsTimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_a_card_without_project_keeps_its_time_on_the_board(): void
    {
        $user = User::factory()->create();
        $board = Board::create(['name' => 'Work', 'user_id' => $user->id]);
        $column = Column::create(['board_id' => $board->id, 'name' => 'Todo', 'position' => 0, 'user_id' => $user->id]);
        $card = Task::create(['column_id' => $column->id, 'title' => 'Card', 'position' => 0, 'user_id' => $user->id]);
        $subtask = Task::create(['column_id' => $column->id, 'parent_task_id' => $card->id, 'title' => 'Sub', 'position' => 0, 'user_id' => $user->id]);

        foreach ([$card, $subtask] as $task) {
            TimeEntry::create([
                'task_id' => $task->id, 'user_id' => $user->id, 'description' => $task->title,
                'start_time' => now()->subHour(), 'end_time' => now()->subMinutes(30),
            ]);
        }

        $this->actingAs($user)->deleteJson("/api/tasks/{$card->id}")->assertSuccessful();

        $this->assertEquals(2, TimeEntry::whereNull('task_id')->where('board_id', $board->id)->count());
        $this->actingAs($user)->getJson("/api/entries?board_id={$board->id}")
            ->assertSuccessful()
            ->assertJsonCount(2)
            ->assertJsonPath('0.board_id', $board->id);
        $this->actingAs($user)->getJson("/api/reports/summary?board_id={$board->id}")
            ->assertJsonPath('total_entries', 2);
    }
}
