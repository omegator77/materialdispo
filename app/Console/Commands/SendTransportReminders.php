<?php

namespace App\Console\Commands;

use App\Mail\TransportReminderMail;
use App\Models\MailingList;
use App\Models\Mietvorgang;
use App\Models\MietvorgangReminderLog;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class SendTransportReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:send-transport';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Versendet Transport-Erinnerungsmails für Mietvorgänge (Mietbeginn/-ende).';

    public function handle(): int
    {
        $today = Carbon::today();

        Mietvorgang::with(['supplier', 'mailingList.recipients', 'items.productions'])
            ->whereHas('items')
            ->get()
            ->each(function (Mietvorgang $mietvorgang) use ($today) {
                $this->maybeSend($mietvorgang, 'start', $mietvorgang->rent_start, $today, $mietvorgang->effectiveReminderDaysBeforeStart());
                $this->maybeSend($mietvorgang, 'end', $mietvorgang->rent_end, $today, $mietvorgang->effectiveReminderDaysBeforeEnd());
            });

        return self::SUCCESS;
    }

    private function maybeSend(Mietvorgang $mietvorgang, string $type, $rentDate, Carbon $today, int $daysBefore): void
    {
        $dueDate = Carbon::parse($rentDate)->subDays($daysBefore);

        if ($today->lessThan($dueDate)) {
            return;
        }

        if ($mietvorgang->reminderLogs()->where('reminder_type', $type)->exists()) {
            return;
        }

        $this->send($mietvorgang, $type);
    }

    private function send(Mietvorgang $mietvorgang, string $type): void
    {
        $recipients = $mietvorgang->mailingList?->recipients->pluck('email') ?? collect();

        if ($recipients->isEmpty()) {
            $recipients = MailingList::where('is_default', true)->first()?->recipients->pluck('email') ?? collect();
        }

        if ($recipients->isEmpty() && ! $mietvorgang->notify_supplier) {
            $this->warn("Mietvorgang #{$mietvorgang->id}: keine Empfänger, Erinnerung übersprungen.");

            return;
        }

        $productions = $mietvorgang->relatedProductions();

        $mailable = new TransportReminderMail($mietvorgang, $type, $productions);

        $addresses = $recipients->values()->all();

        if ($mietvorgang->notify_supplier && $mietvorgang->supplier?->email) {
            $addresses[] = $mietvorgang->supplier->email;
        }

        foreach ($addresses as $email) {
            try {
                Mail::to($email)->send($mailable);
            } catch (\Throwable $e) {
                $this->error("Mietvorgang #{$mietvorgang->id}: Versand an {$email} fehlgeschlagen: {$e->getMessage()}");
            }
        }

        // Wird unabhängig vom Erfolg einzelner Adressen protokolliert, damit ein
        // dauerhaft ungültiger Empfänger nicht bei jedem Lauf erneut alle
        // anderen (bereits erfolgreich erreichten) Empfänger neu anschreibt.
        MietvorgangReminderLog::create([
            'mietvorgang_id' => $mietvorgang->id,
            'reminder_type' => $type,
            'sent_at' => now(),
        ]);
    }
}
