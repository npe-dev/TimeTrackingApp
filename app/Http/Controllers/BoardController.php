<?php
namespace App\Http\Controllers;

use App\Models\Board;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BoardController extends Controller
{
    private array $extensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

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
        foreach ($this->extensions as $ext) {
            Storage::disk('public')->delete("backgrounds/board-{$board->id}.{$ext}");
        }
        $board->delete();
        return response()->json(['success' => true]);
    }

    public function backgroundStatus(Board $board)
    {
        foreach ($this->extensions as $ext) {
            if (Storage::disk('public')->exists("backgrounds/board-{$board->id}.{$ext}")) {
                return response()->json([
                    'exists' => true,
                    'url' => '/storage/backgrounds/board-' . $board->id . '.' . $ext . '?t=' . time(),
                ]);
            }
        }
        return response()->json(['exists' => false]);
    }

    public function uploadBackground(Request $request, Board $board)
    {
        $request->validate(['background' => 'required|image|max:10240']);
        $file = $request->file('background');
        $ext = strtolower($file->getClientOriginalExtension()) ?: 'jpg';
        foreach ($this->extensions as $e) {
            Storage::disk('public')->delete("backgrounds/board-{$board->id}.{$e}");
        }
        $file->storeAs('backgrounds', "board-{$board->id}.{$ext}", 'public');
        return response()->json([
            'success' => true,
            'url' => '/storage/backgrounds/board-' . $board->id . '.' . $ext . '?t=' . time(),
        ]);
    }

    public function deleteBackground(Board $board)
    {
        foreach ($this->extensions as $ext) {
            Storage::disk('public')->delete("backgrounds/board-{$board->id}.{$ext}");
        }
        return response()->json(['success' => true]);
    }
}
