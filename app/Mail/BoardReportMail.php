<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BoardReportMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array  $report  Pre-built report data from ReportService::buildBoardReport().
     */
    public function __construct(
        public array $report,
        public string $recipient,
    ) {}

    public function envelope(): Envelope
    {
        $boardName = $this->report['board']['name'] ?? 'Board';

        return new Envelope(
            subject: "\u{1F4CB} {$boardName} — daily report ({$this->report['generated_at']})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.board-report',
            with: ['report' => $this->report],
        );
    }
}
