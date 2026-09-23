<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BoardInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $boardName,
        public string $invitedBy,
        public string $acceptUrl,
        public string $email,
        public bool $isNewAccount = false,
        public ?string $temporaryPassword = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "{$this->invitedBy} invited you to the \"{$this->boardName}\" board",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.board-invitation',
            with: [
                'boardName' => $this->boardName,
                'invitedBy' => $this->invitedBy,
                'acceptUrl' => $this->acceptUrl,
                'email' => $this->email,
                'isNewAccount' => $this->isNewAccount,
                'temporaryPassword' => $this->temporaryPassword,
            ],
        );
    }
}
