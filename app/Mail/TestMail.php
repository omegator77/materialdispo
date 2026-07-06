<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $sender) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Test-E-Mail von '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.test',
            with: [
                'sender' => $this->sender,
                'sentAt' => now(),
            ],
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
