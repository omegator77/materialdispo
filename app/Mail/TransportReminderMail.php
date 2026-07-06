<?php

namespace App\Mail;

use App\Models\Mietvorgang;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class TransportReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Mietvorgang $mietvorgang,
        public string $reminderType,
        public Collection $productions,
    ) {}

    public function envelope(): Envelope
    {
        $subjectDate = $this->reminderType === 'start'
            ? $this->mietvorgang->rent_start
            : $this->mietvorgang->rent_end;

        $label = $this->reminderType === 'start' ? 'Mietbeginn' : 'Mietende';

        return new Envelope(
            subject: "Transport-Erinnerung: {$label} am {$subjectDate->format('d.m.Y')} — {$this->mietvorgang->supplier->bezeichnung}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.transport-reminder',
            with: [
                'mietvorgang' => $this->mietvorgang,
                'reminderType' => $this->reminderType,
                'reminderLabel' => $this->reminderType === 'start' ? 'Mietbeginn' : 'Mietende',
                'productions' => $this->productions,
                'items' => $this->mietvorgang->items,
                'supplier' => $this->mietvorgang->supplier,
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
