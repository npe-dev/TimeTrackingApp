<?php
namespace App\Http\Controllers;

use App\Models\Board;
use Illuminate\Http\Request;

class BoardController extends Controller
{
    public function index()
    {
        return Board::orderByDesc('created_at')->get();
    }

    public function show(Board $board)
    {
        $board->load('columns');
        return $board;
    }

    public function store(Request $request)
    {
        return Board::create([
            'name' => $request->name,
            'description' => $request->description ?? '',
        ]);
    }

    public function update(Request $request, Board $board)
    {
        $board->update($request->only('name', 'description'));
        return $board;
    }

    public function destroy(Board $board)
    {
        $board->delete();
        return response()->json(['success' => true]);
    }
}
