<?php
namespace App\Http\Controllers;

use App\Models\TaskLabel;
use App\Models\GlobalLabel;
use Illuminate\Http\Request;

class TaskLabelController extends Controller
{
    public function store(Request $request, $taskId)
    {
        if ($request->global_label_id) {
            $existing = TaskLabel::where('task_id', $taskId)
                ->where('global_label_id', $request->global_label_id)
                ->first();
            if ($existing) return $existing;

            $globalLabel = GlobalLabel::find($request->global_label_id);
            return TaskLabel::create([
                'task_id' => $taskId,
                'label' => $globalLabel ? $globalLabel->name : $request->label,
                'color' => $globalLabel ? $globalLabel->color : $request->color,
                'global_label_id' => $request->global_label_id,
            ]);
        }

        return TaskLabel::create([
            'task_id' => $taskId,
            'label' => $request->label,
            'color' => $request->color ?? '#6B7280',
        ]);
    }

    public function destroy(TaskLabel $label)
    {
        $label->delete();
        return response()->json(['success' => true]);
    }
}
