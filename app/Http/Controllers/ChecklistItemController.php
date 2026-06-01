<?php
namespace App\Http\Controllers;

use App\Models\ChecklistItem;
use Illuminate\Http\Request;

class ChecklistItemController extends Controller
{
    public function store(Request $request, $taskId)
    {
        return ChecklistItem::create([
            'task_id' => $taskId,
            'title' => $request->title,
            'position' => $request->position ?? 0,
        ]);
    }

    public function update(Request $request, ChecklistItem $checklistItem)
    {
        $checklistItem->update([
            'title' => $request->title,
            'completed' => (bool) $request->completed,
        ]);
        return $checklistItem;
    }

    public function toggle(ChecklistItem $checklistItem)
    {
        $checklistItem->update(['completed' => !$checklistItem->completed]);
        return $checklistItem;
    }

    public function destroy(ChecklistItem $checklistItem)
    {
        $checklistItem->delete();
        return response()->json(['success' => true]);
    }
}
