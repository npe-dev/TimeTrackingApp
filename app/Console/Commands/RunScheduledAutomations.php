<?php

namespace App\Console\Commands;

use App\Models\Automation;
use App\Services\AutomationService;
use Illuminate\Console\Command;

class RunScheduledAutomations extends Command
{
    protected $signature = 'automations:run-scheduled';

    protected $description = 'Fire time-based automations (schedule + due_date_relative) whose moment has arrived';

    /** Daily time (user timezone) at which due_date_relative automations are evaluated. */
    private const DUE_RELATIVE_CHECK_TIME = '09:00';

    public function handle(): int
    {
        $tz = config('app.user_timezone');
        $now = now($tz);

        $automations = Automation::where('enabled', true)
            ->whereIn('trigger_type', ['schedule', 'due_date_relative'])
            ->get();

        $fired = 0;

        foreach ($automations as $automation) {
            $config = $automation->trigger_config ?? [];

            $due = $automation->trigger_type === 'schedule'
                ? AutomationService::scheduleMatches($config, $now)
                : $now->format('H:i') === self::DUE_RELATIVE_CHECK_TIME;

            if (! $due || static::alreadyRanThisSlot($automation, $now, $tz)) {
                continue;
            }

            if ($automation->trigger_type === 'schedule') {
                AutomationService::runScheduled($automation);
            } else {
                AutomationService::runDueDateRelative($automation);
            }

            // Stamp in UTC (default); last_run_at cast handles the conversion.
            $automation->forceFill(['last_run_at' => now()])->save();
            $fired++;
        }

        $this->info("Fired {$fired} scheduled automation(s).");

        return self::SUCCESS;
    }

    /**
     * Guard against double-firing within the same minute slot (e.g. a manual
     * invocation on top of the scheduler), comparing in the user's timezone.
     */
    private static function alreadyRanThisSlot(Automation $automation, $now, string $tz): bool
    {
        if (! $automation->last_run_at) {
            return false;
        }

        return $automation->last_run_at->copy()->setTimezone($tz)->format('Y-m-d H:i') === $now->format('Y-m-d H:i');
    }
}
