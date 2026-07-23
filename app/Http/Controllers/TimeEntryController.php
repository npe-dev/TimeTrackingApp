<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TimeEntry;
use App\Services\TimerService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TimeEntryController extends Controller
{
    public function index(Request $request)
    {
        $query = TimeEntry::with(['project', 'task'])
            ->where('user_id', $request->user()->id)
            // Exclude the currently-running (open) timer; it is shown separately
            // by the live timer display, not as a completed entry in the list.
            ->whereNotNull('end_time')
            ->orderByDesc('start_time');

        if ($request->board_id) {
            $query->whereHas('project', fn ($q) => $q->where('board_id', $request->board_id));
        }

        if ($request->start_date && $request->end_date) {
            $query->whereDate('start_time', '>=', $request->start_date)
                ->whereDate('start_time', '<=', $request->end_date);
        }

        return $query->get()->map(fn ($e) => $this->formatEntry($e));
    }

    public function running(Request $request)
    {
        $entry = TimerService::running($request->user());

        return $entry ? $this->formatEntry($entry) : null;
    }

    public function start(Request $request)
    {
        $request->validate([
            'project_id' => 'nullable|exists:projects,id',
            'task_id' => 'nullable|exists:tasks,id',
            'description' => 'nullable|string',
        ]);

        $entry = TimerService::start(
            $request->user(),
            $request->project_id,
            $request->task_id,
            $request->description ?? '',
        );

        return $this->formatEntry($entry->load(['project', 'task']));
    }

    public function stop(Request $request)
    {
        TimerService::stop($request->user());

        return response()->json(['success' => true]);
    }

    public function stopAt(Request $request)
    {
        TimeEntry::where('user_id', $request->user()->id)
            ->whereNull('end_time')
            ->update(['end_time' => $request->end_time]);

        return response()->json(['success' => true]);
    }

    public function store(Request $request)
    {
        $entry = TimeEntry::create([
            'project_id' => $request->project_id,
            'description' => $request->description ?? '',
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'user_id' => $request->user()->id,
        ]);

        return $this->formatEntry($entry->load('project'));
    }

    public function update(Request $request, TimeEntry $entry)
    {
        $entry->update([
            'project_id' => $request->project_id,
            'description' => $request->description,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
        ]);

        return $this->formatEntry($entry->load('project'));
    }

    public function destroy(TimeEntry $entry)
    {
        $entry->delete();

        return response()->json(['success' => true]);
    }

    public function heartbeat(Request $request)
    {
        TimeEntry::where('user_id', $request->user()->id)
            ->whereNull('end_time')
            ->update(['last_heartbeat' => Carbon::now()]);

        return response()->json(['success' => true]);
    }

    public function taskEntries($taskId)
    {
        $task = Task::findOrFail($taskId);
        $subtaskIds = $task->subtasks()->pluck('id')->toArray();
        $allIds = array_merge([(int) $taskId], $subtaskIds);

        $entries = TimeEntry::with(['project', 'task'])
            ->whereIn('task_id', $allIds)
            ->orderByDesc('start_time')
            ->get();

        return $entries->map(function ($e) use ($taskId) {
            $arr = $this->formatEntry($e);
            $duration = $e->end_time
                ? $e->start_time->diffInMinutes($e->end_time)
                : $e->start_time->diffInMinutes(now());
            $arr['duration_minutes'] = round($duration, 2);
            if ($e->task_id !== (int) $taskId) {
                $arr['subtask_name'] = $e->task?->title;
            }

            return $arr;
        });
    }

    public function taskStart(Request $request, $taskId)
    {
        $task = Task::findOrFail($taskId);
        $now = Carbon::now();

        TimeEntry::where('user_id', $request->user()->id)
            ->whereNull('end_time')
            ->update(['end_time' => $now]);

        $entry = TimeEntry::create([
            'project_id' => $task->project_id,
            'task_id' => $taskId,
            'description' => $task->title,
            'start_time' => $now,
            'last_heartbeat' => $now,
            'user_id' => $request->user()->id,
        ]);

        return $this->formatEntry($entry->load('project'));
    }

    public function exportCsv(Request $request)
    {
        $query = TimeEntry::with('project')
            ->where('user_id', $request->user()->id);

        if ($request->board_id) {
            $query->whereHas('project', fn ($q) => $q->where('board_id', $request->board_id));
        }

        if ($request->start_date && $request->end_date) {
            $query->whereDate('start_time', '>=', $request->start_date)
                ->whereDate('start_time', '<=', $request->end_date);
        }

        if ($request->task_id) {
            // Include the card's own entries plus all of its subtasks' entries,
            // matching what the card's time list shows (see taskEntries()).
            $task = Task::find($request->task_id);
            $taskIds = $task
                ? array_merge([(int) $request->task_id], $task->subtasks()->pluck('id')->all())
                : [(int) $request->task_id];
            $query->whereIn('task_id', $taskIds);
        }

        $entries = $query->orderBy('start_time')->get();

        $rows = [['ID', 'Project', 'Description', 'Start Time', 'End Time', 'Duration (minutes)', 'Created At']];
        $totalMinutes = 0;

        foreach ($entries as $e) {
            $duration = $e->end_time
                ? $e->start_time->diffInMinutes($e->end_time)
                : $e->start_time->diffInMinutes(now());

            if (round($duration) <= 0) {
                continue;
            }

            $totalMinutes += $duration;
            $rows[] = [
                $e->id,
                '"'.str_replace('"', '""', $e->project?->name ?? 'No Project').'"',
                '"'.str_replace('"', '""', $e->description ?? '').'"',
                $e->start_time->toDateTimeString(),
                $e->end_time ? $e->end_time->toDateTimeString() : 'Running',
                round($duration, 2),
                $e->created_at->toDateTimeString(),
            ];
        }

        $totalRounded = round($totalMinutes);
        $days = intdiv($totalRounded, 8 * 60);
        $rem = $totalRounded % (8 * 60);
        $hours = intdiv($rem, 60);
        $mins = $rem % 60;
        $formatted = ($days > 0 ? "{$days}d " : '').($hours > 0 ? "{$hours}h " : '')."{$mins}m";

        $rows[] = ['', '', '"TOTAL"', '', '', $totalRounded, '"'.trim($formatted).'"'];

        $csv = implode("\n", array_map(fn ($r) => implode(',', $r), $rows));

        $filename = $request->task_id
            ? "task-{$request->task_id}-time-entries-".now()->toDateString().'.csv'
            : 'time-entries-'.now()->toDateString().'.csv';

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    private function formatEntry($entry)
    {
        $arr = $entry->toArray();
        $arr['project_name'] = $entry->project?->name;
        $arr['project_color'] = $entry->project?->color;
        $arr['task_title'] = $entry->task?->title;
        $arr['board_id'] = $entry->project?->board_id;

        return $arr;
    }
}
