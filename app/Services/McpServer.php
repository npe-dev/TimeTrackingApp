<?php

namespace App\Services;

use App\Models\Board;
use App\Models\Column;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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
                'name' => 'create_subtask',
                'description' => 'Create a subtask (child card) under an existing task. The subtask is placed in the same column as its parent.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'parent_task_id' => ['type' => 'integer', 'description' => 'The parent task to attach the subtask to.'],
                        'title' => ['type' => 'string', 'description' => 'The subtask title.'],
                        'description' => ['type' => 'string', 'description' => 'Optional subtask description.'],
                    ],
                    'required' => ['parent_task_id', 'title'],
                ],
            ],
            [
                'name' => 'update_task',
                'description' => 'Update a task (card): change its title, description, priority, due date, completion state, or move it to another column. Only the fields you provide are changed.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'task_id' => ['type' => 'integer', 'description' => 'The task (card) to update.'],
                        'title' => ['type' => 'string', 'description' => 'New title (cannot be empty).'],
                        'description' => ['type' => 'string', 'description' => 'New description.'],
                        'column_id' => ['type' => 'integer', 'description' => 'Move the card to this column (appended to the bottom).'],
                        'priority' => ['type' => 'string', 'enum' => ['none', 'low', 'medium', 'high'], 'description' => 'New priority.'],
                        'due_date' => ['type' => 'string', 'description' => 'Due date (YYYY-MM-DD), or empty string to clear it.'],
                        'completed' => ['type' => 'boolean', 'description' => 'Mark the card done (true) or not done (false).'],
                    ],
                    'required' => ['task_id'],
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
            'create_subtask' => $this->createSubtask($args),
            'update_task' => $this->updateTask($args),
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

    private function createSubtask(array $args): array
    {
        $parent = Task::find($args['parent_task_id'] ?? null);
        if (! $parent) {
            throw new InvalidArgumentException('Parent task not found.');
        }
        $title = trim($args['title'] ?? '');
        if ($title === '') {
            throw new InvalidArgumentException('Title is required.');
        }

        $position = (Task::where('parent_task_id', $parent->id)->max('position') ?? -1) + 1;

        $subtask = Task::create([
            'column_id' => $parent->column_id,
            'project_id' => $parent->project_id,
            'parent_task_id' => $parent->id,
            'title' => $title,
            'description' => $args['description'] ?? '',
            'position' => $position,
        ]);

        AutomationService::run('task_created', ['task_id' => $subtask->id, 'column_id' => $parent->column_id]);

        return [
            'id' => $subtask->id,
            'title' => $subtask->title,
            'parent_task_id' => $parent->id,
            'column_id' => $subtask->column_id,
        ];
    }

    private function updateTask(array $args): array
    {
        $task = Task::find($args['task_id'] ?? null);
        if (! $task) {
            throw new InvalidArgumentException('Task not found.');
        }

        $oldColumnId = $task->column_id;
        $oldPosition = $task->position;
        $oldDueDate = $task->due_date?->toDateString();
        $wasCompleted = (bool) $task->completed_at;

        $updates = [];

        if (array_key_exists('title', $args)) {
            $title = trim((string) $args['title']);
            if ($title === '') {
                throw new InvalidArgumentException('Title cannot be empty.');
            }
            $updates['title'] = $title;
        }
        if (array_key_exists('description', $args)) {
            $updates['description'] = (string) $args['description'];
        }
        if (array_key_exists('priority', $args)) {
            $updates['priority'] = $args['priority'];
        }
        if (array_key_exists('due_date', $args)) {
            $updates['due_date'] = $args['due_date'] !== '' ? $args['due_date'] : null;
        }
        if (array_key_exists('completed', $args)) {
            $updates['completed_at'] = $args['completed'] ? now() : null;
        }

        // Resolve a column move up-front so we can fail fast on a bad column id.
        $movingColumn = array_key_exists('column_id', $args) && (int) $args['column_id'] !== $oldColumnId;
        if ($movingColumn) {
            $newColumn = Column::find((int) $args['column_id']);
            if (! $newColumn) {
                throw new InvalidArgumentException('Column not found.');
            }
        }

        DB::transaction(function () use ($task, $updates, $movingColumn, $args, $oldColumnId, $oldPosition) {
            if ($movingColumn) {
                // Close the gap left in the old column, then append to the bottom
                // of the new one — mirrors TaskController::move position handling.
                Task::where('column_id', $oldColumnId)
                    ->where('position', '>', $oldPosition)
                    ->decrement('position');

                $newColumnId = (int) $args['column_id'];
                $updates['column_id'] = $newColumnId;
                $updates['position'] = (Task::where('column_id', $newColumnId)->max('position') ?? -1) + 1;

                // Keep subtasks in the same column as their parent.
                Task::where('parent_task_id', $task->id)->update(['column_id' => $newColumnId]);
            }

            $task->update($updates);
        });

        // Mirror TaskController: keep a running timer's label/project in sync.
        if (array_key_exists('title', $updates)) {
            $task->timeEntries()->whereNull('end_time')->update(['description' => $task->title]);
        }

        // Fire the same automations the REST endpoints do.
        if ($movingColumn) {
            AutomationService::run('task_moved', [
                'task_id' => $task->id,
                'column_id' => $task->column_id,
                'old_column_id' => $oldColumnId,
                'new_column_id' => $task->column_id,
            ]);
        }
        if (array_key_exists('completed', $args) && $args['completed'] && ! $wasCompleted) {
            AutomationService::run('task_completed', ['task_id' => $task->id, 'column_id' => $task->column_id]);
        }
        $newDueDate = $task->due_date?->toDateString();
        AutomationService::run('task_updated', [
            'task_id' => $task->id,
            'column_id' => $task->column_id,
            'due_date_changed' => $oldDueDate !== $newDueDate,
            'new_due_date' => $newDueDate,
            'old_due_date' => $oldDueDate,
        ]);

        $task->refresh();

        return [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'column_id' => $task->column_id,
            'parent_task_id' => $task->parent_task_id,
            'priority' => $task->priority,
            'due_date' => $task->due_date?->toDateString(),
            'completed' => (bool) $task->completed_at,
        ];
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
