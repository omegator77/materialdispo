<?php

namespace App\Console\Commands;

use App\Models\Bestellung;
use App\Services\SlackBestellungSync;
use App\Services\SlackVorgangSync;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RemindBestellungenCommand extends Command
{
    protected $signature = 'bestellungen:erinnern';

    protected $description = 'Erinnert die Buchhaltung im Slack-Thread an Bestellungen, deren Rechnung seit 30+ Tagen vorliegt und die nicht abgelegt sind.';

    public function handle(SlackBestellungSync $slack): int
    {
        $failures = 0;
        // Toleranz von 1 h: der Claim speichert die tatsächliche Laufzeit (z. B. 09:00:05),
        // der nächste Lauf vergleicht sonst knapp davor und würde erst am Folgetag erinnern.
        $wiederholung = now()->subDays(7)->addHour();

        Bestellung::query()
            ->whereNull('abgelegt_at')
            ->whereNotNull('slack_message_ts')
            ->where('rechnung_vorhanden_at', '<=', now()->subDays(30))
            ->where(fn ($q) => $q->whereNull('letzte_erinnerung_at')->orWhere('letzte_erinnerung_at', '<=', $wiederholung))
            ->orderBy('id')
            ->get()
            ->each(function (Bestellung $bestellung) use ($slack, &$failures, $wiederholung) {
                $previous = $bestellung->letzte_erinnerung_at;

                // Reservieren, BEVOR gesendet wird: überlappende Läufe erinnern nie doppelt.
                $claimed = Bestellung::whereKey($bestellung->id)
                    ->whereNull('abgelegt_at')
                    ->whereNotNull('rechnung_vorhanden_at')
                    ->where(fn ($q) => $q->whereNull('letzte_erinnerung_at')->orWhere('letzte_erinnerung_at', '<=', $wiederholung))
                    ->update(['letzte_erinnerung_at' => now()]);

                if (! $claimed) {
                    return;
                }

                try {
                    $tage = (int) $bestellung->rechnung_vorhanden_at->diffInDays(now());
                    $text = $slack->buchhaltungMentions()
                        .' Rechnung zur Bestellung bei '.SlackVorgangSync::escape((string) $bestellung->bestellt_bei)
                        ." liegt seit {$tage} Tagen vor – bitte bezahlen und ablegen.";
                    $ok = $slack->threadReply($bestellung, $text);
                } catch (\Throwable $e) {
                    Log::warning("Bestellung #{$bestellung->id}: Erinnerung fehlgeschlagen: ".$e->getMessage());
                    $ok = false;
                }

                if (! $ok) {
                    Bestellung::whereKey($bestellung->id)->update(['letzte_erinnerung_at' => $previous]);
                    $failures++;
                    $this->error("Bestellung #{$bestellung->id}: Erinnerung konnte nicht gesendet werden.");
                }
            });

        return $failures > 0 ? self::FAILURE : self::SUCCESS;
    }
}
