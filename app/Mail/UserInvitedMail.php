<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserInvitedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $name,
        public string $email,
        public string $temporaryPassword,
        public string $loginUrl,
        public string $invitedBy,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You have been invited to Time Tracking',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.user-invited',
            with: [
                'name' => $this->name,
                'email' => $this->email,
                'temporaryPassword' => $this->temporaryPassword,
                'loginUrl' => $this->loginUrl,
                'invitedBy' => $this->invitedBy,
            ],
        );
    }
}
