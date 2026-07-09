<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Safety net: stop running timers abandoned by a closed/crashed browser tab.
Schedule::command('timers:stop-stale')->everyMinute();

// Fire time-based automations (e.g. "every day at 9", "every Monday at 10").
// Runs every minute and matches each automation's recurrence against the clock.
Schedule::command('automations:run-scheduled')->everyMinute();

// Send the per-board email report when its configured cadence (frequency +
// time, in the user timezone) matches the current minute.
Schedule::command('reports:send-scheduled')->everyMinute();
