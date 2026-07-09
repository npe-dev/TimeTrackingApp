<?php

namespace App\Console\Commands;

use App\Services\ReportService;
use Illuminate\Console\Command;

class SendScheduledReports extends Command
{
    protected $signature = 'reports:send-scheduled';

    protected $description = 'Send the per-board email report when the configured schedule matches the current minute';

    public function handle(): int
    {
        $sent = ReportService::sendScheduled();

        $this->info("Sent {$sent} board report(s).");

        return self::SUCCESS;
    }
}
