<?php
namespace App\Http\Controllers;

use App\Models\TaskLink;
use Illuminate\Http\Request;

class TaskLinkController extends Controller
{
    public function store(Request $request, $taskId)
    {
        return TaskLink::create([
            'task_id' => $taskId,
            'title' => $request->title,
            'url' => $request->url,
            'position' => $request->position ?? 0,
        ]);
    }

    public function update(Request $request, TaskLink $link)
    {
        $link->update($request->only('title', 'url'));
        return $link;
    }

    public function destroy(TaskLink $link)
    {
        $link->delete();
        return response()->json(['success' => true]);
    }
}
