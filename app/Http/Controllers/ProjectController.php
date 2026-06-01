<?php
namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index()
    {
        return Project::orderBy('name')->get();
    }

    public function store(Request $request)
    {
        $project = Project::create([
            'name' => $request->name,
            'color' => $request->color ?? '#3B82F6',
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
