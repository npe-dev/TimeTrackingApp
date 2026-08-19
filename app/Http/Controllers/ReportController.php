<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function summary(Request $request)
    {
        $userId = $request->user()->id;
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $boardId = $request->board_id;
        $projectId = $request->project_id;

        $minutesExpr = "SUM(CASE WHEN e.end_time IS NULL THEN (julianday('now', 'localtime') - julianday(e.start_time)) * 24 * 60 ELSE (julianday(e.end_time) - julianday(e.start_time)) * 24 * 60 END)";

        $byProject = DB::table('time_entries as e')
            ->leftJoin('projects as p', 'e.project_id', '=', 'p.id')
            ->where('e.user_id', $userId)
            ->when($boardId, fn ($q) => $q->where('p.board_id', $boardId))
            ->when($projectId, fn ($q) => $q->where('e.project_id', $projectId))
            ->when($startDate && $endDate, fn ($q) => $q->whereDate('e.start_time', '>=', $startDate)->whereDate('e.start_time', '<=', $endDate))
            ->select(
                'p.id', 'p.name', 'p.color',
                DB::raw('COUNT(e.id) as entries'),
                DB::raw("$minutesExpr as minutes")
            )
            ->groupBy('p.id', 'p.name', 'p.color')
            ->orderByDesc('minutes')
            ->get();

        $byDay = DB::table('time_entries as e')
            ->leftJoin('projects as p', 'e.project_id', '=', 'p.id')
            ->where('e.user_id', $userId)
            ->when($boardId, fn ($q) => $q->where('p.board_id', $boardId))
            ->when($projectId, fn ($q) => $q->where('e.project_id', $projectId))
            ->when($startDate && $endDate, fn ($q) => $q->whereDate('e.start_time', '>=', $startDate)->whereDate('e.start_time', '<=', $endDate))
            ->select(
                DB::raw('date(e.start_time) as date'),
                DB::raw('COUNT(*) as entries'),
                DB::raw("$minutesExpr as minutes")
            )
            ->groupBy(DB::raw('date(e.start_time)'))
            ->orderBy(DB::raw('date(e.start_time)'))
            ->get();

        $totalMinutes = (float) $byProject->sum('minutes');
        $totalEntries = (int) $byProject->sum('entries');
        $daysCount = max($byDay->count(), 1);

        return response()->json([
            'total_minutes' => round($totalMinutes),
            'total_entries' => $totalEntries,
            'average_per_day' => round($totalMinutes / $daysCount),
            'by_project' => $byProject,
            'by_day' => $byDay,
        ]);
    }
}
