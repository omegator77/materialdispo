<?php

namespace App\Console\Commands;

use App\Models\Mietvorgang;
use App\Models\Production;
use App\Models\Vermietvorgang;
use App\Services\SlackVorgangSync;
use Illuminate\Console\Command;

/**
 * Verschiebt die Slack-Nachricht eines einzelnen Vorgangs in einen neu
 * eingestellten Kanal: render() bevorzugt immer den am Vorgang gespeicherten
 * slack_channel gegenüber der Settings-Einstellung, daher reicht ein reiner
 * Re-Sync nach einer Kanal-Änderung nicht aus. Setzt slack_channel/
 * slack_message_ts zurück und postet danach neu (über die aktuelle
 * Settings-Einstellung) — die alte Nachricht im bisherigen Kanal bleibt
 * unverändert stehen.
 */
class ReassignSlackChannel extends Command
{
    protected $signature = 'slack:reassign-channel {type : mietvorgang|vermietvorgang|production} {search : Teil der Bezeichnung zur eindeutigen Identifikation}';

    protected $description = 'Postet die Slack-Nachricht eines Vorgangs neu im aktuell eingestellten Kanal, statt im bisherigen weiter zu aktualisieren.';

    public function handle(SlackVorgangSync $slack): int
    {
        $type = $this->argument('type');
        $search = $this->argument('search');

        $modelClass = match ($type) {
            'mietvorgang' => Mietvorgang::class,
            'vermietvorgang' => Vermietvorgang::class,
            'production' => Production::class,
            default => null,
        };

        if (! $modelClass) {
            $this->error("Unbekannter Typ '{$type}'. Erlaubt: mietvorgang, vermietvorgang, production.");

            return self::FAILURE;
        }

        $matches = $modelClass::query()
            ->whereNotNull('slack_message_ts')
            ->where('bezeichnung', 'like', "%{$search}%")
            ->get();

        if ($matches->count() !== 1) {
            $this->error($matches->isEmpty()
                ? 'Kein Vorgang mit gespeicherter Slack-Nachricht gefunden, der zur Suche passt.'
                : 'Suche ist nicht eindeutig, bitte präzisieren:');

            foreach ($matches as $match) {
                $this->line("  #{$match->id} \"{$match->bezeichnung}\" (aktueller Kanal: {$match->slack_channel})");
            }

            return self::FAILURE;
        }

        $vorgang = $matches->first();
        $this->info("Gefunden: #{$vorgang->id} \"{$vorgang->bezeichnung}\" (bisheriger Kanal: {$vorgang->slack_channel}).");

        $vorgang->forceFill([
            'slack_channel' => null,
            'slack_message_ts' => null,
        ])->saveQuietly();

        match ($type) {
            'mietvorgang' => $slack->syncMietvorgang($vorgang),
            'vermietvorgang' => $slack->syncVermietvorgang($vorgang),
            'production' => $slack->syncProduction($vorgang),
        };

        $vorgang->refresh();

        if (! $vorgang->slack_message_ts) {
            $this->error('Neue Nachricht konnte nicht gepostet werden — Log prüfen.');

            return self::FAILURE;
        }

        $this->info("Neue Nachricht gepostet in Kanal {$vorgang->slack_channel} (ts: {$vorgang->slack_message_ts}).");

        return self::SUCCESS;
    }
}
