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
            ->orderBy('id')
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
        $columnId = $request->column_id;
        $parentTaskId = $request->parent_task_id;

        // Always append new cards to the bottom. The client can't reliably compute
        // the bottom position from its visible list: archived tasks keep their
        // position but are hidden, so gaps make a count-based position collide with
        // an existing card and land mid-column. Derive it authoritatively from the
        // max position among siblings (same column + same parent scope) instead.
        $siblings = Task::where('column_id', $columnId);
        $parentTaskId === null
            ? $siblings->whereNull('parent_task_id')
            : $siblings->where('parent_task_id', $parentTaskId);
        $position = ($siblings->max('position') ?? -1) + 1;

        $task = Task::create([
            'column_id' => $columnId,
            'project_id' => $request->project_id,
            'parent_task_id' => $parentTaskId,
            'title' => $request->title,
            'description' => $request->description ?? '',
            'due_date' => $request->due_date,
            'priority' => $request->priority ?? 'none',
            'position' => $position,
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
        // The client sends a *visible index* (0-based slot among the cards it can
        // see), not an absolute position value. Treat it as such and rebuild the
        // column's positions from that index. The old increment/decrement math
        // assumed positions were a contiguous 0..n-1 run, but they aren't:
        // getBottomPosition jumps by max+1, archived cards keep (hidden) positions,
        // and automations could leave duplicates — so a "drop at the end" index
        // collided with a real card and landed the card mid-column.
        $targetIndex = (int) $request->position;
        $oldColumnId = $task->column_id;

        DB::transaction(function () use ($task, $newColumnId, $targetIndex, $oldColumnId) {
            // Sibling scope must match what the board renders and indexes against:
            // top-level, non-archived cards in the column, in (position, id) order.
            $siblings = Task::where('column_id', $newColumnId)
                ->whereNull('parent_task_id')
                ->whereNull('archived_at')
                ->where('id', '!=', $task->id)
                ->orderBy('position')
                ->orderBy('id')
                ->get()
                ->all();

            $index = max(0, min($targetIndex, count($siblings)));
            array_splice($siblings, $index, 0, [$task]);

            foreach ($siblings as $pos => $sibling) {
                if ($sibling->id === $task->id) {
                    $task->update(['column_id' => $newColumnId, 'position' => $pos]);
                } elseif ($sibling->position !== $pos) {
                    $sibling->update(['position' => $pos]);
                }
            }

            if ($oldColumnId !== $newColumnId) {
                // Compact the source column so it too stays a clean 0..n-1 run.
                $sourceSiblings = Task::where('column_id', $oldColumnId)
                    ->whereNull('parent_task_id')
                    ->whereNull('archived_at')
                    ->orderBy('position')
                    ->orderBy('id')
                    ->get();
                foreach ($sourceSiblings as $pos => $sibling) {
                    if ($sibling->position !== $pos) {
                        $sibling->update(['position' => $pos]);
                    }
                }

                // Keep subtasks in the same column as their parent.
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
