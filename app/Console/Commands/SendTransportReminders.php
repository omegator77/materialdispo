<?php

namespace App\Console\Commands;

use App\Mail\TransportReminderMail;
use App\Mail\VerleihReminderMail;
use App\Models\MailingList;
use App\Models\Mietvorgang;
use App\Models\Vermietvorgang;
use App\Services\SlackVorgangSync;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\UniqueConstraintViolationException;
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
    protected $description = 'Versendet Transport-Erinnerungsmails für Mietvorgänge und Vermietvorgänge (Beginn/Ende).';

    /** Anzahl Erinnerungen, bei denen ALLE Mails fehlgeschlagen sind. */
    private int $failures = 0;

    public function __construct(private SlackVorgangSync $slack)
    {
        parent::__construct();
    }

    /**
     * Eine Erinnerung ist fällig ab (Termin − Vorlauftage) und nur bis zum
     * Termin selbst: Für bereits vergangene Termine (nachträglich angelegte
     * Vorgänge, Altdaten) wird keine Mail mehr nachgeschickt.
     */
    private function isDue(Carbon $today, $date, int $daysBefore): bool
    {
        $date = Carbon::parse($date);

        return $today->greaterThanOrEqualTo($date->copy()->subDays($daysBefore))
            && $today->lessThanOrEqualTo($date);
    }

    /**
     * Reserviert die Erinnerung (Unique-Index je Vorgang+Typ), BEVOR gesendet
     * wird: Ein Absturz oder zwei überlappende Läufe führen so nie zu doppelten
     * Mails. Gibt null zurück, wenn sie schon reserviert/versendet wurde.
     */
    private function claim(HasMany $logs, string $type): ?Model
    {
        try {
            return $logs->create(['reminder_type' => $type, 'sent_at' => now()]);
        } catch (UniqueConstraintViolationException) {
            return null;
        }
    }

    /**
     * Versendet die Mail an alle Adressen. Gibt false zurück, wenn es Adressen
     * gab und KEINE davon erreicht wurde — dann wird die Reservierung wieder
     * freigegeben und beim nächsten Lauf erneut versucht. Teilerfolge zählen als
     * gesendet, damit bereits erreichte Empfänger nicht erneut angeschrieben werden.
     *
     * @param  array<int, string>  $addresses
     */
    private function deliver(array $addresses, $mailable, string $label): bool
    {
        $reached = 0;

        foreach ($addresses as $email) {
            try {
                Mail::to($email)->send($mailable);
                $reached++;
            } catch (\Throwable $e) {
                $this->error("{$label}: Versand an {$email} fehlgeschlagen: {$e->getMessage()}");
            }
        }

        return $addresses === [] || $reached > 0;
    }

    public function handle(): int
    {
        $this->failures = 0;
        $today = Carbon::today();

        Mietvorgang::with(['supplier', 'mailingList.recipients', 'items.productions'])
            ->whereHas('items')
            ->get()
            ->each(function (Mietvorgang $mietvorgang) use ($today) {
                $this->maybeSend($mietvorgang, 'start', $mietvorgang->rent_start, $today, $mietvorgang->effectiveReminderDaysBeforeStart());
                $this->maybeSend($mietvorgang, 'end', $mietvorgang->rent_end, $today, $mietvorgang->effectiveReminderDaysBeforeEnd());
            });

        Vermietvorgang::with(['mieter', 'mailingList.recipients', 'items.productions'])
            ->whereHas('items')
            ->get()
            ->each(function (Vermietvorgang $vermietvorgang) use ($today) {
                $this->maybeSendVermietung($vermietvorgang, 'start', $vermietvorgang->rent_start, $today, $vermietvorgang->effectiveReminderDaysBeforeStart());
                $this->maybeSendVermietung($vermietvorgang, 'end', $vermietvorgang->rent_end, $today, $vermietvorgang->effectiveReminderDaysBeforeEnd());
            });

        return $this->failures > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function maybeSend(Mietvorgang $mietvorgang, string $type, $rentDate, Carbon $today, int $daysBefore): void
    {
        if (! $this->isDue($today, $rentDate, $daysBefore)) {
            return;
        }

        if (! ($claim = $this->claim($mietvorgang->reminderLogs(), $type))) {
            return;
        }

        if (! $this->send($mietvorgang, $type)) {
            $claim->delete();
            $this->failures++;
        }
    }

    /**
     * @return bool false, wenn alle Mails fehlgeschlagen sind (dann kein Slack-Ping)
     */
    private function send(Mietvorgang $mietvorgang, string $type): bool
    {
        $recipients = $mietvorgang->mailingList?->recipients->pluck('email') ?? collect();

        if ($recipients->isEmpty()) {
            $recipients = MailingList::where('is_default', true)->first()?->recipients->pluck('email') ?? collect();
        }

        if ($recipients->isEmpty() && ! $mietvorgang->notify_supplier) {
            $this->warn("Mietvorgang #{$mietvorgang->id}: keine E-Mail-Empfänger, E-Mail übersprungen.");
        } else {
            $addresses = $recipients->values()->all();

            if ($mietvorgang->notify_supplier && $mietvorgang->supplier?->email) {
                $addresses[] = $mietvorgang->supplier->email;
            }

            $mailable = new TransportReminderMail($mietvorgang, $type, $mietvorgang->relatedProductions());

            if (! $this->deliver($addresses, $mailable, "Mietvorgang #{$mietvorgang->id}")) {
                return false;
            }
        }

        $this->sendSlack($mietvorgang, $type);

        return true;
    }

    private function sendSlack(Mietvorgang $mietvorgang, string $type): void
    {
        $label = $type === 'start' ? 'Mietbeginn' : 'Mietende';
        $transportLabel = $type === 'start' ? ($mietvorgang->transport_type_start ?: '–') : ($mietvorgang->transport_type_end ?: '–');

        $this->slack->threadReply($mietvorgang, "🚚 Erinnerung: {$label} steht an (Transportart: ".SlackVorgangSync::escape($transportLabel).').');
    }

    private function maybeSendVermietung(Vermietvorgang $vermietvorgang, string $type, $rentDate, Carbon $today, int $daysBefore): void
    {
        if (! $this->isDue($today, $rentDate, $daysBefore)) {
            return;
        }

        if (! ($claim = $this->claim($vermietvorgang->reminderLogs(), $type))) {
            return;
        }

        if (! $this->sendVermietung($vermietvorgang, $type)) {
            $claim->delete();
            $this->failures++;
        }
    }

    /**
     * @return bool false, wenn alle Mails fehlgeschlagen sind (dann kein Slack-Ping)
     */
    private function sendVermietung(Vermietvorgang $vermietvorgang, string $type): bool
    {
        $recipients = $vermietvorgang->mailingList?->recipients->pluck('email') ?? collect();

        if ($recipients->isEmpty()) {
            $recipients = MailingList::where('is_default', true)->first()?->recipients->pluck('email') ?? collect();
        }

        if ($recipients->isEmpty() && ! $vermietvorgang->notify_mieter) {
            $this->warn("Vermietvorgang #{$vermietvorgang->id}: keine E-Mail-Empfänger, E-Mail übersprungen.");
        } else {
            $addresses = $recipients->values()->all();

            if ($vermietvorgang->notify_mieter && $vermietvorgang->mieter?->email) {
                $addresses[] = $vermietvorgang->mieter->email;
            }

            $mailable = new VerleihReminderMail($vermietvorgang, $type, $vermietvorgang->relatedProductions());

            if (! $this->deliver($addresses, $mailable, "Vermietvorgang #{$vermietvorgang->id}")) {
                return false;
            }
        }

        $this->sendVermietungSlack($vermietvorgang, $type);

        return true;
    }

    private function sendVermietungSlack(Vermietvorgang $vermietvorgang, string $type): void
    {
        $label = $type === 'start' ? 'Verleihbeginn' : 'Verleihende';
        $transportLabel = $type === 'start' ? ($vermietvorgang->transport_type_start ?: '–') : ($vermietvorgang->transport_type_end ?: '–');

        $this->slack->threadReply($vermietvorgang, "🚚 Erinnerung: {$label} steht an (Transportart: ".SlackVorgangSync::escape($transportLabel).').');
    }
}
