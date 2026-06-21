<?php

namespace App\Console\Commands;

use App\Models\TimeEntry;
use Illuminate\Console\Command;

class StopStaleTimers extends Command
{
    protected $signature = 'timers:stop-stale';

    protected $description = 'Stop running timers whose last heartbeat is older than the idle threshold (safety net for closed/crashed browser tabs)';

    public function handle(): int
    {
        $cutoff = now()->subMinutes((int) config('timer.idle_minutes', 10));

        $stale = TimeEntry::whereNull('end_time')
            ->where(function ($query) use ($cutoff) {
                $query->where('last_heartbeat', '<', $cutoff)
                    ->orWhere(function ($query) use ($cutoff) {
                        // Defensive: a running entry with no heartbeat at all
                        $query->whereNull('last_heartbeat')->where('start_time', '<', $cutoff);
                    });
            })
            ->get();

        foreach ($stale as $entry) {
            // Backdate the stop to the last known activity so idle time isn't counted,
            // matching the client-side stopAt(idleStartTime) behaviour.
            $entry->update(['end_time' => $entry->last_heartbeat ?? $entry->start_time]);
        }

        $this->info("Stopped {$stale->count()} stale timer(s).");

        return self::SUCCESS;
    }
}
