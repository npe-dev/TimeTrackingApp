<?php

namespace App\Http\Controllers;

use App\Models\Column;
use App\Models\Task;
use App\Services\AutomationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    public function index($columnId)
    {
        $tasks = Task::with([
            'project',
            'labels.globalLabel',
            'checklistItems',
            'subtasks' => fn ($q) => $q->whereNull('archived_at'),
            'subtasks.labels.globalLabel',
            'subtasks.checklistItems',
            'subtasks.project',
        ])
            ->where('column_id', $columnId)
            ->whereNull('parent_task_id')
            ->whereNull('archived_at')
            ->orderBy('position')
            ->get();

        return $tasks->map(function ($task) {
            $arr = $this->formatTaskForList($task);
            $arr['subtasks'] = $task->subtasks->map(fn ($st) => $this->formatTaskForList($st))->values();

            return $arr;
        });
    }

    private function formatTaskForList(Task $task): array
    {
        $labels = $task->labels->map(function ($tl) {
            return [
                'id' => $tl->id,
                'task_id' => $tl->task_id,
                'label' => $tl->globalLabel ? $tl->globalLabel->name : $tl->label,
                'color' => $tl->globalLabel ? $tl->globalLabel->color : $tl->color,
                'global_label_id' => $tl->global_label_id,
                'created_at' => $tl->created_at,
            ];
        });
        $arr = $task->toArray();
        $arr['labels'] = $labels;
        unset($arr['labels_relation'], $arr['subtasks']);
        $task->loadMissing('project');
        $arr['project_name'] = $task->project?->name;
        $arr['project_color'] = $task->project?->color;
        $arr['checklist_total'] = $task->checklistItems->count();
        $arr['checklist_done'] = $task->checklistItems->where('completed', true)->count();

        return $arr;
    }

    public function show(Task $task)
    {
        $task->load(['labels.globalLabel', 'checklistItems', 'links', 'project', 'subtasks']);

        $labels = $task->labels->map(fn ($tl) => [
            'id' => $tl->id,
            'task_id' => $tl->task_id,
            'label' => $tl->globalLabel ? $tl->globalLabel->name : $tl->label,
            'color' => $tl->globalLabel ? $tl->globalLabel->color : $tl->color,
            'global_label_id' => $tl->global_label_id,
            'created_at' => $tl->created_at,
        ]);

        $result = $task->toArray();
        $result['labels'] = $labels;
        $result['checklist'] = $task->checklistItems;
        $result['links'] = $task->links;
        $result['project_name'] = $task->project?->name;
        $result['project_color'] = $task->project?->color;

        return $result;
    }

    public function store(Request $request)
    {
        $task = Task::create([
            'column_id' => $request->column_id,
            'project_id' => $request->project_id,
            'parent_task_id' => $request->parent_task_id,
            'title' => $request->title,
            'description' => $request->description ?? '',
            'due_date' => $request->due_date,
            'priority' => $request->priority ?? 'none',
            'position' => $request->position ?? 0,
        ]);

        $task->load('project');
        $result = $task->toArray();
        $result['project_name'] = $task->project?->name;
        $result['project_color'] = $task->project?->color;

        AutomationService::run('task_created', ['task_id' => $task->id, 'column_id' => $request->column_id]);

        return $result;
    }

    public function update(Request $request, Task $task)
    {
        $oldTask = $task->toArray();

        $task->update([
            'column_id' => $request->column_id,
            'project_id' => $request->project_id,
            'title' => $request->title,
            'description' => $request->description,
            'due_date' => $request->due_date,
            'priority' => $request->priority,
            'position' => $request->position,
        ]);

        // Update running timer if exists
        $task->timeEntries()
            ->whereNull('end_time')
            ->update([
                'description' => $request->title,
                'project_id' => $request->project_id,
            ]);

        $task->load('project');
        $result = $task->toArray();
        $result['project_name'] = $task->project?->name;
        $result['project_color'] = $task->project?->color;

        $dueDateChanged = ($oldTask['due_date'] ?? null) !== ($request->due_date ?? null);
        AutomationService::run('task_updated', [
            'task_id' => $task->id,
            'column_id' => $request->column_id,
            'due_date_changed' => $dueDateChanged,
            'new_due_date' => $request->due_date,
            'old_due_date' => $oldTask['due_date'] ?? null,
        ]);

        return $result;
    }

    public function move(Request $request, Task $task)
    {
        $newColumnId = (int) $request->column_id;
        $newPosition = (int) $request->position;
        $oldColumnId = $task->column_id;
        $oldPosition = $task->position;

        DB::transaction(function () use ($task, $newColumnId, $newPosition, $oldColumnId, $oldPosition) {
            if ($oldColumnId === $newColumnId) {
                if ($newPosition < $oldPosition) {
                    Task::where('column_id', $newColumnId)
                        ->where('position', '>=', $newPosition)
                        ->where('position', '<', $oldPosition)
                        ->where('id', '!=', $task->id)
                        ->increment('position');
                } elseif ($newPosition > $oldPosition) {
                    Task::where('column_id', $newColumnId)
                        ->where('position', '>', $oldPosition)
                        ->where('position', '<=', $newPosition)
                        ->where('id', '!=', $task->id)
                        ->decrement('position');
                }
            } else {
                Task::where('column_id', $oldColumnId)
                    ->where('position', '>', $oldPosition)
                    ->decrement('position');

                Task::where('column_id', $newColumnId)
                    ->where('position', '>=', $newPosition)
                    ->increment('position');
            }

            $task->update(['column_id' => $newColumnId, 'position' => $newPosition]);

            // Move subtasks to the same column
            if ($oldColumnId !== $newColumnId) {
                Task::where('parent_task_id', $task->id)->update(['column_id' => $newColumnId]);
            }
        });

        if ($oldColumnId !== $newColumnId) {
            AutomationService::run('task_moved', [
                'task_id' => $task->id,
                'column_id' => $newColumnId,
                'old_column_id' => $oldColumnId,
                'new_column_id' => $newColumnId,
            ]);
        }

        return $task->fresh();
    }

    public function fixPositions()
    {
        $columns = Column::all();
        DB::transaction(function () use ($columns) {
            foreach ($columns as $column) {
                $tasks = Task::where('column_id', $column->id)
                    ->orderBy('position')->orderBy('id')->get();
                foreach ($tasks as $index => $task) {
                    $task->update(['position' => $index]);
                }
            }
        });

        return response()->json(['success' => true, 'message' => 'Positions normalized']);
    }

    public function toggleComplete(Task $task)
    {
        $wasCompleted = (bool) $task->completed_at;
        $task->update([
            'completed_at' => $task->completed_at ? null : now(),
        ]);

        // Fire the "card done" automation only when transitioning into the done state.
        if (! $wasCompleted) {
            AutomationService::run('task_completed', [
                'task_id' => $task->id,
                'column_id' => $task->column_id,
            ]);
        }

        return $task->fresh();
    }

    public function reorderSubtasks(Request $request, Task $task)
    {
        $ids = $request->input('subtask_ids', []);
        foreach ($ids as $position => $id) {
            Task::where('id', $id)->where('parent_task_id', $task->id)->update(['position' => $position]);
        }

        return response()->json(['success' => true]);
    }

    public function destroy(Task $task)
    {
        AutomationService::run('task_deleted', ['task_id' => $task->id, 'column_id' => $task->column_id]);
        $task->delete();

        return response()->json(['success' => true]);
    }
}
