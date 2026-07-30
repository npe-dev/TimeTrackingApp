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

        foreach (['list_boards', 'list_tasks', 'create_task', 'create_subtask', 'update_task', 'start_timer', 'stop_timer', 'get_running_timer'] as $tool) {
            $this->assertContains($tool, $names);
        }
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
