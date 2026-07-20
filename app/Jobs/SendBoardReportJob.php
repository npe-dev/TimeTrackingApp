<?php

namespace App\Jobs;

use App\Models\Board;
use App\Services\ReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendBoardReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array  $report  Pre-built report data from ReportService::buildBoardReport().
     */
    public function __construct(
        public int $boardId,
        public array $report,
        public string $recipient,
    ) {}

    public function handle(): void
    {
        $board = Board::find($this->boardId);

        if (! $board) {
            return;
        }

        ReportService::deliver($board, $this->report, $this->recipient);
    }
}
