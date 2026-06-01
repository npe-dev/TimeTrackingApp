<?php
namespace App\Http\Controllers;

use App\Models\Column;
use Illuminate\Http\Request;

class ColumnController extends Controller
{
    public function index($boardId)
    {
        return Column::where('board_id', $boardId)->orderBy('position')->get();
    }

    public function store(Request $request)
    {
        return Column::create([
            'board_id' => $request->board_id,
            'name' => $request->name,
            'position' => $request->position ?? 0,
        ]);
    }

    public function update(Request $request, Column $column)
    {
        $column->update($request->only('name', 'position'));
        return $column;
    }

    public function destroy(Column $column)
    {
        if ($column->tasks()->count() > 0) {
            return response()->json(['error' => 'Cannot delete a column that still has tasks. Move or delete all tasks first.'], 400);
        }
        $column->delete();
        return response()->json(['success' => true]);
    }
}
