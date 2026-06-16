<?php
namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\GlobalLabel;
use Illuminate\Http\Request;

class GlobalLabelController extends Controller
{
    public function index(Board $board)
    {
        return $board->labels;
    }

    public function store(Request $request, Board $board)
    {
        $maxOrder = $board->labels()->max('sort_order') ?? -1;
        return GlobalLabel::create([
            'board_id' => $board->id,
            'name' => $request->name,
            'color' => $request->color ?? '#6B7280',
            'sort_order' => $maxOrder + 1,
        ]);
    }

    public function update(Request $request, Board $board, GlobalLabel $globalLabel)
    {
        $globalLabel->update($request->only('name', 'color'));
        return $globalLabel;
    }

    public function destroy(Board $board, GlobalLabel $globalLabel)
    {
        $globalLabel->delete();
        return response()->json(['success' => true]);
    }

    public function reorder(Request $request, Board $board)
    {
        $labelIds = $request->input('labelIds', []);
        foreach ($labelIds as $index => $id) {
            GlobalLabel::where('id', $id)->where('board_id', $board->id)->update(['sort_order' => $index]);
        }
        return $board->labels;
    }
}
