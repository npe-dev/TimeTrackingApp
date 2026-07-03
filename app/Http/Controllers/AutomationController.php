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

        return $query->orderByDesc('created_at')->get()->map(fn ($a) => $this->formatAutomation($a));
    }

    public function show(Automation $automation)
    {
        return $this->formatAutomation($automation);
    }

    public function runs(Automation $automation)
    {
        return $automation->runs()->limit(100)->get(['id', 'status', 'message', 'created_at']);
    }

    public function store(Request $request)
    {
        $trigger = $request->input('trigger', []);

        $automation = Automation::create([
            'name' => $request->name ?? '',
            'board_id' => $request->board_id,
            'trigger_type' => $trigger['type'] ?? $request->trigger_type,
            'trigger_config' => collect($trigger)->except('type')->all() ?: $request->trigger_config,
            'actions' => $request->actions,
        ]);

        return $this->formatAutomation($automation);
    }

    public function update(Request $request, Automation $automation)
    {
        $trigger = $request->input('trigger', []);

        $automation->update([
            'name' => $request->name ?? '',
            'board_id' => $request->board_id,
            'trigger_type' => $trigger['type'] ?? $request->trigger_type ?? $automation->trigger_type,
            'trigger_config' => collect($trigger)->except('type')->all() ?: $request->trigger_config ?? $automation->trigger_config,
            'actions' => $request->actions,
        ]);

        return $this->formatAutomation($automation);
    }

    private function formatAutomation(Automation $automation): array
    {
        $data = $automation->toArray();
        $data['trigger'] = array_merge(
            ['type' => $automation->trigger_type],
            $automation->trigger_config ?? []
        );

        return $data;
    }

    public function destroy(Automation $automation)
    {
        $automation->delete();

        return response()->json(['success' => true]);
    }

    public function toggle(Automation $automation)
    {
        $automation->update(['enabled' => ! $automation->enabled]);

        return response()->json(['success' => true, 'enabled' => $automation->enabled]);
    }
}
