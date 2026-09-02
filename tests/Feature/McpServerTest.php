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

class McpServerTest extends TestCase
{
    use RefreshDatabase;

    private function rpc(User $user, array $body)
    {
        return $this->actingAs($user)->postJson('/api/mcp', $body);
    }

    public function test_initialize_returns_server_info(): void
    {
        $user = User::factory()->create();

        $this->rpc($user, ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => []])
            ->assertSuccessful()
            ->assertJsonPath('result.serverInfo.name', 'TimeTracking')
            ->assertJsonPath('id', 1);
    }

    public function test_tools_list_returns_the_starter_tools(): void
    {
        $user = User::factory()->create();

        $names = collect(
            $this->rpc($user, ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/list'])
                ->assertSuccessful()
                ->json('result.tools')
        )->pluck('name');

        foreach ([
            'list_boards', 'list_tasks', 'create_task', 'create_subtask', 'update_task',
            'start_timer', 'stop_timer', 'get_running_timer',
            'list_projects', 'list_time_entries', 'time_summary', 'update_time_entry', 'delete_time_entry',
        ] as $tool) {
            $this->assertContains($tool, $names);
        }
    }

    private function callTool(User $user, string $name, array $arguments)
    {
        return $this->rpc($user, [
            'jsonrpc' => '2.0',
            'id' => 42,
            'method' => 'tools/call',
            'params' => ['name' => $name, 'arguments' => $arguments],
        ])->assertSuccessful()->assertJsonPath('result.isError', false);
    }

    /** Decode the JSON text payload a tool returns in result.content[0].text. */
    private function toolResult($response): mixed
    {
        return json_decode($response->json('result.content.0.text'), true);
    }

    public function test_list_projects_tool_returns_projects(): void
    {
        $user = User::factory()->create();
        $board = Board::create(['name' => 'Work']);
        Project::create(['board_id' => $board->id, 'name' => 'Alpha']);

        $data = $this->toolResult($this->callTool($user, 'list_projects', ['board_id' => $board->id]));

        $this->assertCount(1, $data);
        $this->assertSame('Alpha', $data[0]['name']);
        $this->assertSame($board->id, $data[0]['board_id']);
    }

    public function test_list_time_entries_returns_completed_entries_with_duration(): void
    {
        $user = User::factory()->create();
        $board = Board::create(['name' => 'Work']);
        $project = Project::create(['board_id' => $board->id, 'name' => 'Alpha']);

        // A 16h entry (the exact case that motivated this) plus the running timer.
        $completed = TimeEntry::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'description' => 'Overnight',
            'start_time' => '2026-09-01 16:28:00',
            'end_time' => '2026-09-02 08:28:00',
        ]);
        TimeEntry::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'description' => 'Running',
            'start_time' => '2026-09-02 10:56:00',
            'end_time' => null,
        ]);

        $data = $this->toolResult($this->callTool($user, 'list_time_entries', ['board_id' => $board->id]));

        // Running timer excluded; completed entry present with a 16h duration.
        $this->assertCount(1, $data);
        $this->assertSame($completed->id, $data[0]['id']);
        $this->assertSame(960, $data[0]['duration_minutes']);
        $this->assertSame('16h 0m', $data[0]['duration_label']);
    }

    public function test_time_summary_groups_by_project(): void
    {
        $user = User::factory()->create();
        $board = Board::create(['name' => 'Work']);
        $project = Project::create(['board_id' => $board->id, 'name' => 'Alpha']);

        TimeEntry::create([
            'project_id' => $project->id, 'user_id' => $user->id,
            'start_time' => '2026-09-01 09:00:00', 'end_time' => '2026-09-01 10:30:00',
        ]);
        TimeEntry::create([
            'project_id' => $project->id, 'user_id' => $user->id,
            'start_time' => '2026-09-01 11:00:00', 'end_time' => '2026-09-01 11:30:00',
        ]);

        $data = $this->toolResult($this->callTool($user, 'time_summary', ['board_id' => $board->id]));

        $this->assertSame(120, $data['total_minutes']);
        $this->assertSame('2h 0m', $data['total_label']);
        $this->assertSame('Alpha', $data['projects'][0]['project']);
        $this->assertSame(120, $data['projects'][0]['minutes']);
    }

    public function test_update_time_entry_corrects_the_end_time(): void
    {
        $user = User::factory()->create();
        $board = Board::create(['name' => 'Work']);
        $project = Project::create(['board_id' => $board->id, 'name' => 'Alpha']);
        $entry = TimeEntry::create([
            'project_id' => $project->id, 'user_id' => $user->id,
            'start_time' => '2026-09-01 16:28:00', 'end_time' => '2026-09-02 08:28:00',
        ]);

        $this->callTool($user, 'update_time_entry', [
            'entry_id' => $entry->id,
            'end_time' => '2026-09-01 18:28:00',
        ]);

        $entry->refresh();
        $this->assertSame('2026-09-01 18:28:00', $entry->end_time->toDateTimeString());
    }

    public function test_update_time_entry_rejects_end_before_start(): void
    {
        $user = User::factory()->create();
        $board = Board::create(['name' => 'Work']);
        $project = Project::create(['board_id' => $board->id, 'name' => 'Alpha']);
        $entry = TimeEntry::create([
            'project_id' => $project->id, 'user_id' => $user->id,
            'start_time' => '2026-09-01 16:00:00', 'end_time' => '2026-09-01 17:00:00',
        ]);

        $this->rpc($user, [
            'jsonrpc' => '2.0', 'id' => 43, 'method' => 'tools/call',
            'params' => ['name' => 'update_time_entry', 'arguments' => [
                'entry_id' => $entry->id,
                'end_time' => '2026-09-01 15:00:00',
            ]],
        ])->assertSuccessful()->assertJsonPath('result.isError', true);
    }

    public function test_delete_time_entry_removes_the_entry(): void
    {
        $user = User::factory()->create();
        $board = Board::create(['name' => 'Work']);
        $project = Project::create(['board_id' => $board->id, 'name' => 'Alpha']);
        $entry = TimeEntry::create([
            'project_id' => $project->id, 'user_id' => $user->id,
            'start_time' => '2026-09-01 16:00:00', 'end_time' => '2026-09-01 17:00:00',
        ]);

        $this->callTool($user, 'delete_time_entry', ['entry_id' => $entry->id]);

        $this->assertDatabaseMissing('time_entries', ['id' => $entry->id]);
    }

    public function test_time_entry_tools_are_scoped_to_the_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $board = Board::create(['name' => 'Work']);
        $project = Project::create(['board_id' => $board->id, 'name' => 'Alpha']);
        $foreign = TimeEntry::create([
            'project_id' => $project->id, 'user_id' => $other->id,
            'start_time' => '2026-09-01 16:00:00', 'end_time' => '2026-09-01 17:00:00',
        ]);

        // Not visible to $user…
        $data = $this->toolResult($this->callTool($user, 'list_time_entries', []));
        $this->assertCount(0, $data);

        // …and not deletable by $user.
        $this->rpc($user, [
            'jsonrpc' => '2.0', 'id' => 44, 'method' => 'tools/call',
            'params' => ['name' => 'delete_time_entry', 'arguments' => ['entry_id' => $foreign->id]],
        ])->assertSuccessful()->assertJsonPath('result.isError', true);

        $this->assertDatabaseHas('time_entries', ['id' => $foreign->id]);
    }

    public function test_initialized_notification_returns_no_content(): void
    {
        $user = User::factory()->create();

        $this->rpc($user, ['jsonrpc' => '2.0', 'method' => 'notifications/initialized'])
            ->assertNoContent(202);
    }

    public function test_start_timer_tool_starts_a_timer(): void
    {
        $user = User::factory()->create();
        $board = Board::create(['name' => 'Work']);
        $project = Project::create(['board_id' => $board->id, 'name' => 'Alpha']);

        $response = $this->rpc($user, [
            'jsonrpc' => '2.0',
            'id' => 3,
            'method' => 'tools/call',
            'params' => ['name' => 'start_timer', 'arguments' => ['project_id' => $project->id]],
        ])->assertSuccessful();

        $this->assertFalse($response->json('result.isError'));
        $this->assertNotNull(TimeEntry::where('user_id', $user->id)->whereNull('end_time')->first());
    }

    public function test_create_task_tool_creates_a_card(): void
    {
        $user = User::factory()->create();
        $board = Board::create(['name' => 'Work']);
        $column = Column::create(['board_id' => $board->id, 'name' => 'To Do', 'position' => 0]);

        $this->rpc($user, [
            'jsonrpc' => '2.0',
            'id' => 4,
            'method' => 'tools/call',
            'params' => ['name' => 'create_task', 'arguments' => ['column_id' => $column->id, 'title' => 'From MCP']],
        ])->assertSuccessful()->assertJsonPath('result.isError', false);

        $this->assertDatabaseHas('tasks', ['title' => 'From MCP', 'column_id' => $column->id]);
    }

    public function test_update_task_tool_edits_fields(): void
    {
        $user = User::factory()->create();
        $board = Board::create(['name' => 'Work']);
        $column = Column::create(['board_id' => $board->id, 'name' => 'To Do', 'position' => 0]);
        $task = Task::create(['column_id' => $column->id, 'title' => 'Old', 'position' => 0]);

        $this->rpc($user, [
            'jsonrpc' => '2.0',
            'id' => 8,
            'method' => 'tools/call',
            'params' => ['name' => 'update_task', 'arguments' => [
                'task_id' => $task->id,
                'title' => 'New',
                'priority' => 'high',
                'completed' => true,
            ]],
        ])->assertSuccessful()->assertJsonPath('result.isError', false);

        $task->refresh();
        $this->assertSame('New', $task->title);
        $this->assertSame('high', $task->priority);
        $this->assertNotNull($task->completed_at);
    }

    public function test_update_task_tool_moves_card_to_another_column(): void
    {
        $user = User::factory()->create();
        $board = Board::create(['name' => 'Work']);
        $todo = Column::create(['board_id' => $board->id, 'name' => 'To Do', 'position' => 0]);
        $done = Column::create(['board_id' => $board->id, 'name' => 'Done', 'position' => 1]);
        $task = Task::create(['column_id' => $todo->id, 'title' => 'Move me', 'position' => 0]);

        $this->rpc($user, [
            'jsonrpc' => '2.0',
            'id' => 9,
            'method' => 'tools/call',
            'params' => ['name' => 'update_task', 'arguments' => [
                'task_id' => $task->id,
                'column_id' => $done->id,
            ]],
        ])->assertSuccessful()->assertJsonPath('result.isError', false);

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'column_id' => $done->id]);
    }

    public function test_create_subtask_tool_creates_a_child_card(): void
    {
        $user = User::factory()->create();
        $board = Board::create(['name' => 'Work']);
        $column = Column::create(['board_id' => $board->id, 'name' => 'To Do', 'position' => 0]);
        $parent = Task::create(['column_id' => $column->id, 'title' => 'Parent', 'position' => 0]);

        $this->rpc($user, [
            'jsonrpc' => '2.0',
            'id' => 10,
            'method' => 'tools/call',
            'params' => ['name' => 'create_subtask', 'arguments' => [
                'parent_task_id' => $parent->id,
                'title' => 'Child',
            ]],
        ])->assertSuccessful()->assertJsonPath('result.isError', false);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Child',
            'parent_task_id' => $parent->id,
            'column_id' => $column->id,
        ]);
    }

    public function test_mcp_endpoint_authenticates_with_a_bearer_token(): void
    {
        $user = User::factory()->create();
        $plain = $user->createToken('mcp')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$plain}")
            ->postJson('/api/mcp', ['jsonrpc' => '2.0', 'id' => 5, 'method' => 'tools/list'])
            ->assertSuccessful()
            ->assertJsonPath('id', 5);
    }

    public function test_mcp_endpoint_rejects_unauthenticated_requests(): void
    {
        $this->postJson('/api/mcp', ['jsonrpc' => '2.0', 'id' => 6, 'method' => 'tools/list'])
            ->assertStatus(401);
    }

    public function test_unknown_method_returns_jsonrpc_error(): void
    {
        $user = User::factory()->create();

        $this->rpc($user, ['jsonrpc' => '2.0', 'id' => 7, 'method' => 'bogus/method'])
            ->assertSuccessful()
            ->assertJsonPath('error.code', -32601);
    }
}
