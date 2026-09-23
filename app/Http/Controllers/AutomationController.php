<?php

namespace App\Http\Controllers;

use App\Models\Automation;
use App\Models\Board;
use Illuminate\Http\Request;

class AutomationController extends Controller
{
    public function index(Request $request)
    {
        // Automations are an owner-only feature. Restrict to boards the user owns
        // (a shared board's automations belong to its owner, not its members).
        if ($request->board_id) {
            $this->ensureBoardOwner(Board::findOrFail($request->board_id));
            $query = Automation::where('board_id', $request->board_id);
        } else {
            $ownedBoardIds = Board::where('user_id', $request->user()->id)->pluck('id');
            $query = Automation::whereIn('board_id', $ownedBoardIds);
        }

        return $query->orderByDesc('created_at')->get()->map(fn ($a) => $this->formatAutomation($a));
    }

    public function show(Automation $automation)
    {
        $this->ensureBoardOwner($automation->board);

        return $this->formatAutomation($automation);
    }

    public function runs(Automation $automation)
    {
        $this->ensureBoardOwner($automation->board);

        return $automation->runs()->limit(100)->get(['id', 'status', 'message', 'created_at']);
    }

    public function store(Request $request)
    {
        // 404s (via the ownership scope) if the board isn't accessible; then
        // require ownership — only the owner manages a board's automations.
        $this->ensureBoardOwner(Board::findOrFail($request->board_id));

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
        // Require ownership of both the automation's current board and the target.
        $this->ensureBoardOwner($automation->board);
        $this->ensureBoardOwner(Board::findOrFail($request->board_id));

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
        $this->ensureBoardOwner($automation->board);
        $automation->delete();

        return response()->json(['success' => true]);
    }

    public function toggle(Automation $automation)
    {
        $this->ensureBoardOwner($automation->board);
        $automation->update(['enabled' => ! $automation->enabled]);

        return response()->json(['success' => true, 'enabled' => $automation->enabled]);
    }
}
