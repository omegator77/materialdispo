<?php

namespace App\Mail;

use App\Models\DryHire;
use App\Models\Production;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DryHireReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Production $production,
        public DryHire $dryHire,
        public string $reminderType,
    ) {}

    public function envelope(): Envelope
    {
        $subjectDate = Carbon::parse($this->reminderType === 'start'
            ? $this->production->booking_start
            : $this->production->booking_end);

        $label = $this->reminderType === 'start' ? 'Übergabe' : 'Rückgabe';

        return new Envelope(
            subject: "Dry-Hire-Erinnerung: {$label} am {$subjectDate->format('d.m.Y')} — {$this->production->bezeichnung}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.dry-hire-reminder',
            with: [
                'production' => $this->production,
                'dryHire' => $this->dryHire,
                'reminderType' => $this->reminderType,
                'reminderLabel' => $this->reminderType === 'start' ? 'Übergabe an Kunde' : 'Rückgabe vom Kunden',
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
