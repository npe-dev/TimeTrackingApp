<?php

namespace App\Services;

use App\Models\Board;
use App\Models\Column;
use App\Models\Task;
use App\Models\User;
use InvalidArgumentException;

/**
 * Minimal MCP tool registry. Each tool exposes a JSON-Schema input and a handler
 * that runs against the existing domain models on behalf of the given user.
 */
class McpServer
{
    public const SERVER_NAME = 'TimeTracking';

    public const SERVER_VERSION = '1.0.0';

    /**
     * Tool definitions advertised via tools/list.
     */
    public function tools(): array
    {
        return [
            [
                'name' => 'list_boards',
                'description' => 'List all boards (id, name, description).',
                'inputSchema' => ['type' => 'object', 'properties' => (object) []],
            ],
            [
                'name' => 'list_tasks',
                'description' => 'List the columns of a board and the tasks within each column.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'board_id' => ['type' => 'integer', 'description' => 'The board to list tasks for.'],
                    ],
                    'required' => ['board_id'],
                ],
            ],
            [
                'name' => 'create_task',
                'description' => 'Create a new task (card) at the bottom of a column.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'column_id' => ['type' => 'integer', 'description' => 'The column to add the task to.'],
                        'title' => ['type' => 'string', 'description' => 'The task title.'],
                        'description' => ['type' => 'string', 'description' => 'Optional task description.'],
                    ],
                    'required' => ['column_id', 'title'],
                ],
            ],
            [
                'name' => 'start_timer',
                'description' => 'Start a time entry. Provide a project_id, or a task_id (its project, or the board default, is used).',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'project_id' => ['type' => 'integer', 'description' => 'Project to track time against.'],
                        'task_id' => ['type' => 'integer', 'description' => 'Task to track time against.'],
                        'description' => ['type' => 'string', 'description' => 'Optional description of what you are working on.'],
                    ],
                ],
            ],
            [
                'name' => 'stop_timer',
                'description' => 'Stop the currently running time entry, if any.',
                'inputSchema' => ['type' => 'object', 'properties' => (object) []],
            ],
            [
                'name' => 'get_running_timer',
                'description' => 'Get the currently running time entry, if any.',
                'inputSchema' => ['type' => 'object', 'properties' => (object) []],
            ],
        ];
    }

    /**
     * Execute a tool by name and return a plain PHP value (encoded to text by the controller).
     */
    public function call(string $name, array $args, User $user): mixed
    {
        return match ($name) {
            'list_boards' => $this->listBoards(),
            'list_tasks' => $this->listTasks($args),
            'create_task' => $this->createTask($args),
            'start_timer' => $this->startTimer($args, $user),
            'stop_timer' => $this->stopTimer($user),
            'get_running_timer' => $this->runningTimer($user),
            default => throw new InvalidArgumentException("Unknown tool: {$name}"),
        };
    }

    private function listBoards(): array
    {
        return Board::orderByDesc('created_at')
            ->get(['id', 'name', 'description'])
            ->toArray();
    }

    private function listTasks(array $args): array
    {
        $board = Board::with('columns.tasks')->find($args['board_id'] ?? null);
        if (! $board) {
            throw new InvalidArgumentException('Board not found.');
        }

        return [
            'board' => ['id' => $board->id, 'name' => $board->name],
            'columns' => $board->columns->map(fn (Column $col) => [
                'id' => $col->id,
                'name' => $col->name,
                'tasks' => $col->tasks->map(fn (Task $t) => [
                    'id' => $t->id,
                    'title' => $t->title,
                    'completed' => (bool) $t->completed_at,
                    'parent_task_id' => $t->parent_task_id,
                ])->values(),
            ])->values(),
        ];
    }

    private function createTask(array $args): array
    {
        $column = Column::find($args['column_id'] ?? null);
        if (! $column) {
            throw new InvalidArgumentException('Column not found.');
        }
        $title = trim($args['title'] ?? '');
        if ($title === '') {
            throw new InvalidArgumentException('Title is required.');
        }

        $position = (Task::where('column_id', $column->id)->max('position') ?? -1) + 1;

        $task = Task::create([
            'column_id' => $column->id,
            'title' => $title,
            'description' => $args['description'] ?? '',
            'position' => $position,
        ]);

        AutomationService::run('task_created', ['task_id' => $task->id, 'column_id' => $column->id]);

        return ['id' => $task->id, 'title' => $task->title, 'column_id' => $column->id];
    }

    private function startTimer(array $args, User $user): array
    {
        $entry = TimerService::start(
            $user,
            isset($args['project_id']) ? (int) $args['project_id'] : null,
            isset($args['task_id']) ? (int) $args['task_id'] : null,
            $args['description'] ?? '',
        );
        $entry->load(['project', 'task']);

        return [
            'started' => true,
            'entry_id' => $entry->id,
            'project' => $entry->project?->name,
            'task' => $entry->task?->title,
            'start_time' => $entry->start_time?->toIso8601String(),
        ];
    }

    private function stopTimer(User $user): array
    {
        $running = TimerService::running($user);
        TimerService::stop($user);

        return ['stopped' => (bool) $running];
    }

    private function runningTimer(User $user): array
    {
        $entry = TimerService::running($user);
        if (! $entry) {
            return ['running' => false];
        }

        return [
            'running' => true,
            'entry_id' => $entry->id,
            'project' => $entry->project?->name,
            'task' => $entry->task?->title,
            'description' => $entry->description,
            'start_time' => $entry->start_time?->toIso8601String(),
        ];
    }
}
