<?php
namespace App\Http\Controllers;

use App\Models\Automation;
use Illuminate\Http\Request;

class AutomationController extends Controller
{
    public function index(Request $request)
    {
        $query = Automation::query();
        if ($request->board_id) {
            $query->where('board_id', $request->board_id);
        }
        return $query->orderByDesc('created_at')->get();
    }

    public function show(Automation $automation)
    {
        return $automation;
    }

    public function store(Request $request)
    {
        return Automation::create([
            'name' => $request->name,
            'board_id' => $request->board_id,
            'trigger_type' => $request->trigger_type,
            'trigger_config' => $request->trigger_config,
            'actions' => $request->actions,
        ]);
    }

    public function update(Request $request, Automation $automation)
    {
        $automation->update([
            'name' => $request->name,
            'board_id' => $request->board_id,
            'trigger_type' => $request->trigger_type,
            'trigger_config' => $request->trigger_config,
            'actions' => $request->actions,
            'enabled' => (bool) $request->enabled,
        ]);
        return $automation;
    }

    public function destroy(Automation $automation)
    {
        $automation->delete();
        return response()->json(['success' => true]);
    }

    public function toggle(Automation $automation)
    {
        $automation->update(['enabled' => !$automation->enabled]);
        return response()->json(['success' => true, 'enabled' => $automation->enabled]);
    }
}
