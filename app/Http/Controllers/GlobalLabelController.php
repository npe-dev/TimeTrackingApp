<?php
namespace App\Http\Controllers;

use App\Models\GlobalLabel;
use Illuminate\Http\Request;

class GlobalLabelController extends Controller
{
    public function index()
    {
        return GlobalLabel::orderBy('sort_order')->orderBy('id')->get();
    }

    public function store(Request $request)
    {
        $maxOrder = GlobalLabel::max('sort_order') ?? -1;
        return GlobalLabel::create([
            'name' => $request->name,
            'color' => $request->color ?? '#6B7280',
            'sort_order' => $maxOrder + 1,
        ]);
    }

    public function update(Request $request, GlobalLabel $globalLabel)
    {
        $globalLabel->update($request->only('name', 'color'));
        return $globalLabel;
    }

    public function destroy(GlobalLabel $globalLabel)
    {
        $globalLabel->delete();
        return response()->json(['success' => true]);
    }

    public function reorder(Request $request)
    {
        $labelIds = $request->input('labelIds', []);
        foreach ($labelIds as $index => $id) {
            GlobalLabel::where('id', $id)->update(['sort_order' => $index]);
        }
        return GlobalLabel::orderBy('sort_order')->orderBy('id')->get();
    }
}
