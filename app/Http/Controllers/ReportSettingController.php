<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\ReportRun;
use App\Models\ReportSetting;
use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportSettingController extends Controller
{
    /** Labels for the toggleable report blocks (order = render order). */
    private const SECTION_LABELS = [
        'critical' => 'Critical — due this week + overdue',
        'columns' => 'Summary — task count per column',
        'time' => 'Time tracked this week',
        'completed' => 'Completed this week',
    ];

    /**
     * Return the global report settings plus per-board opt-in state and the
     * latest delivery status for each board.
     */
    public function show(Request $request)
    {
        $settings = ReportSetting::current();

        $latestRuns = ReportRun::whereIn('id', function ($q) {
            $q->selectRaw('MAX(id)')->from('report_runs')->groupBy('board_id');
        })->get()->keyBy('board_id');

        $boards = Board::orderBy('name')->get()->map(fn (Board $b) => [
            'id' => $b->id,
            'name' => $b->name,
            'report_enabled' => $b->report_enabled,
            'last_run' => optional($latestRuns->get($b->id), fn ($r) => [
                'status' => $r->status,
                'message' => $r->message,
                'sent_at' => $r->sent_at,
                'at' => $r->created_at,
            ]),
        ]);

        return response()->json([
            'settings' => $settings,
            'boards' => $boards,
            'account_email' => optional($request->user())->email,
            'available_sections' => collect(self::SECTION_LABELS)
                ->map(fn ($label, $key) => ['key' => $key, 'label' => $label])
                ->values(),
        ]);
    }

    /** Update the global report schedule / sections / recipient. */
    public function update(Request $request)
    {
        $data = $request->validate([
            'enabled' => 'required|boolean',
            'frequency' => 'required|in:daily,weekdays,weekly',
            'day_of_week' => 'nullable|integer|min:1|max:7',
            'time' => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'sections' => 'array',
            'sections.*' => 'in:'.implode(',', array_keys(self::SECTION_LABELS)),
            'recipient_email' => 'nullable|email',
        ]);

        // day_of_week only meaningful for weekly cadence.
        if ($data['frequency'] !== 'weekly') {
            $data['day_of_week'] = null;
        } elseif (empty($data['day_of_week'])) {
            $data['day_of_week'] = 1;
        }

        $settings = ReportSetting::current();
        $settings->update($data);

        return response()->json($settings->fresh());
    }

    /** Toggle a single board's opt-in for the report. */
    public function toggleBoard(Request $request, Board $board)
    {
        $board->update(['report_enabled' => ! $board->report_enabled]);

        return response()->json(['success' => true, 'report_enabled' => $board->report_enabled]);
    }

    /** Build the report for a board and return its data (UI preview). */
    public function preview(Request $request, Board $board)
    {
        return response()->json(ReportService::buildBoardReport($board));
    }

    /** Send the report for a board immediately (test), even if empty. */
    public function sendNow(Request $request, Board $board)
    {
        $sent = ReportService::sendForBoard($board, null, force: true);

        return response()->json([
            'success' => $sent,
            'message' => $sent
                ? 'Report sent.'
                : 'Could not send — check the mail configuration and recipient email.',
        ]);
    }
}
