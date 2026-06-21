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

        $byProject = DB::table('time_entries as e')
            ->leftJoin('projects as p', 'e.project_id', '=', 'p.id')
            ->where('e.user_id', $userId)
            ->when($boardId, fn ($q) => $q->where('p.board_id', $boardId))
            ->when($startDate && $endDate, fn ($q) => $q->whereDate('e.start_time', '>=', $startDate)->whereDate('e.start_time', '<=', $endDate))
            ->select(
                'p.id', 'p.name', 'p.color',
                DB::raw('COUNT(e.id) as entry_count'),
                DB::raw("SUM(CASE WHEN e.end_time IS NULL THEN (julianday('now', 'localtime') - julianday(e.start_time)) * 24 * 60 ELSE (julianday(e.end_time) - julianday(e.start_time)) * 24 * 60 END) as total_minutes")
            )
            ->groupBy('p.id', 'p.name', 'p.color')
            ->orderByDesc('total_minutes')
            ->get();

        $byDay = DB::table('time_entries as e')
            ->leftJoin('projects as p', 'e.project_id', '=', 'p.id')
            ->where('e.user_id', $userId)
            ->when($boardId, fn ($q) => $q->where('p.board_id', $boardId))
            ->when($startDate && $endDate, fn ($q) => $q->whereDate('e.start_time', '>=', $startDate)->whereDate('e.start_time', '<=', $endDate))
            ->select(
                DB::raw('date(e.start_time) as date'),
                DB::raw('COUNT(*) as entry_count'),
                DB::raw("SUM(CASE WHEN e.end_time IS NULL THEN (julianday('now', 'localtime') - julianday(e.start_time)) * 24 * 60 ELSE (julianday(e.end_time) - julianday(e.start_time)) * 24 * 60 END) as total_minutes")
            )
            ->groupBy(DB::raw('date(e.start_time)'))
            ->orderByDesc(DB::raw('date(e.start_time)'))
            ->get();

        return response()->json(['byProject' => $byProject, 'byDay' => $byDay]);
    }
}
