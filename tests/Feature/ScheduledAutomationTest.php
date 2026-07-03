<?php

namespace Tests\Feature;

use App\Models\Automation;
use App\Models\AutomationRun;
use App\Models\Board;
use App\Models\Column;
use App\Models\GlobalLabel;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ScheduledAutomationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Evaluate schedules in a fixed, DST-free zone for deterministic assertions.
        config(['app.user_timezone' => 'UTC']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function board(): Board
    {
        return Board::create(['name' => 'Work']);
    }

    public function test_daily_schedule_creates_a_card_once_per_slot(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-01 09:00:00', 'UTC')); // Wednesday
        $board = $this->board();
        $col = Column::create(['board_id' => $board->id, 'name' => 'Inbox', 'position' => 0]);

        Automation::create([
            'name' => 'Daily standup',
            'board_id' => $board->id,
            'trigger_type' => 'schedule',
            'trigger_config' => ['frequency' => 'daily', 'time' => '09:00'],
            'actions' => [['type' => 'create_card', 'column_id' => $col->id, 'title' => 'Standup notes']],
            'enabled' => true,
        ]);

        $this->artisan('automations:run-scheduled')->assertExitCode(0);
        $this->assertSame(1, Task::where('column_id', $col->id)->count());

        // Re-running within the same minute must not create a duplicate.
        $this->artisan('automations:run-scheduled')->assertExitCode(0);
        $this->assertSame(1, Task::where('column_id', $col->id)->count());
    }

    public function test_schedule_does_not_fire_at_a_different_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-01 08:00:00', 'UTC'));
        $board = $this->board();
        $col = Column::create(['board_id' => $board->id, 'name' => 'Inbox', 'position' => 0]);

        Automation::create([
            'name' => 'Off-hour',
            'board_id' => $board->id,
            'trigger_type' => 'schedule',
            'trigger_config' => ['frequency' => 'daily', 'time' => '09:00'],
            'actions' => [['type' => 'create_card', 'column_id' => $col->id, 'title' => 'X']],
            'enabled' => true,
        ]);

        $this->artisan('automations:run-scheduled');
        $this->assertSame(0, Task::where('column_id', $col->id)->count());
    }

    public function test_weekly_schedule_only_fires_on_the_chosen_weekday(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-01 10:00:00', 'UTC')); // Wednesday (isoWeekday 3)
        $board = $this->board();
        $col = Column::create(['board_id' => $board->id, 'name' => 'Inbox', 'position' => 0]);

        Automation::create([
            'name' => 'Weekly',
            'board_id' => $board->id,
            'trigger_type' => 'schedule',
            'trigger_config' => ['frequency' => 'weekly', 'time' => '10:00', 'day_of_week' => 1], // Monday
            'actions' => [['type' => 'create_card', 'column_id' => $col->id, 'title' => 'X']],
            'enabled' => true,
        ]);

        $this->artisan('automations:run-scheduled');
        $this->assertSame(0, Task::where('column_id', $col->id)->count());

        // Same automation on the matching weekday (Monday) fires.
        Carbon::setTestNow(Carbon::parse('2026-07-06 10:00:00', 'UTC')); // Monday
        $this->artisan('automations:run-scheduled');
        $this->assertSame(1, Task::where('column_id', $col->id)->count());
    }

    public function test_bulk_move_moves_all_cards_between_columns(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-01 00:00:00', 'UTC'));
        $board = $this->board();
        $from = Column::create(['board_id' => $board->id, 'name' => 'Done', 'position' => 0]);
        $to = Column::create(['board_id' => $board->id, 'name' => 'Archive', 'position' => 1]);
        Task::create(['column_id' => $from->id, 'title' => 'A', 'position' => 0]);
        Task::create(['column_id' => $from->id, 'title' => 'B', 'position' => 1]);

        Automation::create([
            'name' => 'Clear done',
            'board_id' => $board->id,
            'trigger_type' => 'schedule',
            'trigger_config' => ['frequency' => 'daily', 'time' => '00:00'],
            'actions' => [['type' => 'bulk_move', 'from_column_id' => $from->id, 'to_column_id' => $to->id, 'position' => 'bottom']],
            'enabled' => true,
        ]);

        $this->artisan('automations:run-scheduled');

        $this->assertSame(0, Task::where('column_id', $from->id)->count());
        $this->assertSame(2, Task::where('column_id', $to->id)->count());
    }

    public function test_due_date_relative_runs_for_matching_cards(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-01 09:00:00', 'UTC'));
        $board = $this->board();
        $todo = Column::create(['board_id' => $board->id, 'name' => 'To Do', 'position' => 0]);
        $label = GlobalLabel::create(['board_id' => $board->id, 'name' => 'Soon', 'color' => '#f00']);

        // Due in exactly 2 days -> should match "2 days before due date".
        $match = Task::create(['column_id' => $todo->id, 'title' => 'Due soon', 'due_date' => '2026-07-03', 'position' => 0]);
        // Due in 5 days -> should not match.
        $noMatch = Task::create(['column_id' => $todo->id, 'title' => 'Later', 'due_date' => '2026-07-06', 'position' => 1]);

        Automation::create([
            'name' => 'Flag soon-due',
            'board_id' => $board->id,
            'trigger_type' => 'due_date_relative',
            'trigger_config' => ['days' => 2, 'direction' => 'before'],
            'actions' => [['type' => 'add_label', 'label_id' => $label->id]],
            'enabled' => true,
        ]);

        $this->artisan('automations:run-scheduled');

        $this->assertSame(1, $match->labels()->count());
        $this->assertSame(0, $noMatch->labels()->count());
    }

    public function test_scheduled_run_is_recorded_in_history(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-01 09:00:00', 'UTC'));
        $board = $this->board();
        $col = Column::create(['board_id' => $board->id, 'name' => 'Inbox', 'position' => 0]);

        $automation = Automation::create([
            'name' => 'Daily standup',
            'board_id' => $board->id,
            'trigger_type' => 'schedule',
            'trigger_config' => ['frequency' => 'daily', 'time' => '09:00'],
            'actions' => [['type' => 'create_card', 'column_id' => $col->id, 'title' => 'Standup notes']],
            'enabled' => true,
        ]);

        $this->artisan('automations:run-scheduled');

        $run = AutomationRun::where('automation_id', $automation->id)->first();
        $this->assertNotNull($run);
        $this->assertSame('success', $run->status);
        $this->assertStringContainsString('Created card "Standup notes"', $run->message);
    }

    public function test_event_automation_records_a_run(): void
    {
        $user = User::factory()->create();
        $board = $this->board();
        $todo = Column::create(['board_id' => $board->id, 'name' => 'To Do', 'position' => 0]);
        $done = Column::create(['board_id' => $board->id, 'name' => 'Done', 'position' => 1]);
        $task = Task::create(['column_id' => $todo->id, 'title' => 'Ship it', 'position' => 0]);

        $automation = Automation::create([
            'name' => 'Move done cards',
            'board_id' => $board->id,
            'trigger_type' => 'card_done',
            'trigger_config' => [],
            'actions' => [['type' => 'move_card', 'column_id' => $done->id, 'position' => 'bottom']],
            'enabled' => true,
        ]);

        $this->actingAs($user)->patchJson("/api/tasks/{$task->id}/toggle-complete")->assertSuccessful();

        $run = AutomationRun::where('automation_id', $automation->id)->first();
        $this->assertNotNull($run);
        $this->assertSame('success', $run->status);
        $this->assertStringContainsString("task #{$task->id}", $run->message);
    }

    public function test_archived_tasks_are_hidden_from_the_board(): void
    {
        $board = $this->board();
        $col = Column::create(['board_id' => $board->id, 'name' => 'Inbox', 'position' => 0]);
        $visible = Task::create(['column_id' => $col->id, 'title' => 'Visible', 'position' => 0]);
        Task::create(['column_id' => $col->id, 'title' => 'Hidden', 'position' => 1, 'archived_at' => now()]);

        $user = User::factory()->create();
        $response = $this->actingAs($user)->getJson("/api/columns/{$col->id}/tasks")->assertSuccessful();

        $titles = collect($response->json())->pluck('title');
        $this->assertTrue($titles->contains('Visible'));
        $this->assertFalse($titles->contains('Hidden'));
    }
}
