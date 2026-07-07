<?php

namespace App\Mail;

use App\Models\Vermietvorgang;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class VerleihReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Vermietvorgang $vermietvorgang,
        public string $reminderType,
        public Collection $productions,
    ) {}

    public function envelope(): Envelope
    {
        $subjectDate = $this->reminderType === 'start'
            ? $this->vermietvorgang->rent_start
            : $this->vermietvorgang->rent_end;

        $label = $this->reminderType === 'start' ? 'Verleihbeginn' : 'Verleihende';

        return new Envelope(
            subject: "Transport-Erinnerung: {$label} am {$subjectDate->format('d.m.Y')} — {$this->vermietvorgang->mieter->bezeichnung}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.verleih-reminder',
            with: [
                'vermietvorgang' => $this->vermietvorgang,
                'reminderType' => $this->reminderType,
                'reminderLabel' => $this->reminderType === 'start' ? 'Verleihbeginn' : 'Verleihende',
                'productions' => $this->productions,
                'items' => $this->vermietvorgang->items,
                'mieter' => $this->vermietvorgang->mieter,
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
