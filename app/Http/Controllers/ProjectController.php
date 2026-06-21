<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Project::orderBy('name');
        if ($request->board_id) {
            $query->where('board_id', $request->board_id);
        }

        return $query->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'board_id' => 'required|exists:boards,id',
            'name' => 'required|string',
            'color' => 'nullable|string',
        ]);

        $project = Project::create([
            'board_id' => $validated['board_id'],
            'name' => $validated['name'],
            'color' => $validated['color'] ?? '#3B82F6',
        ]);

        return $project;
    }

    public function update(Request $request, Project $project)
    {
        $project->update($request->only('name', 'color'));

        return $project;
    }

    public function destroy(Project $project)
    {
        $project->delete();

        return response()->json(['success' => true]);
    }
}
