<?php

namespace App\Services;

use App\Models\Mietvorgang;
use App\Models\Production;
use App\Models\Setting;
use App\Models\Vermietvorgang;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Hält je Mietvorgang/Vermietvorgang genau eine Slack-Nachricht über die
 * gesamte Laufzeit aktuell (Details + Bestätigungsstatus aller vier Haken).
 * Wird sowohl nach App-seitigen Änderungen (Controller, Item-Zuordnung) als
 * auch nach Slack-Button-Klicks aufgerufen, damit beide Seiten denselben
 * Zustand zeigen. Solange keine Nachricht existiert, wird eine neue gepostet
 * (channel/ts werden am Vorgang gespeichert); danach läuft jede Änderung über
 * chat.update auf genau diese Nachricht.
 */
class SlackVorgangSync
{
    /**
     * Maskiert die Slack-mrkdwn-Steuerzeichen (&, <, >) in Nutzereingaben.
     * Sonst könnten Bezeichnungen/Namen mit "<!channel>" Massen-Pings oder mit
     * "<https://…|Text>" untergeschobene Links im Kanal auslösen. Nur für Werte
     * aus der Datenbank verwenden, nicht für selbst gebaute "<url|text>"-Links.
     */
    public static function escape(?string $text): string
    {
        return str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], (string) $text);
    }

    public function syncMietvorgang(Mietvorgang $mietvorgang): void
    {
        if (! $mietvorgang->suppliers_id || ! $mietvorgang->rent_start || ! $mietvorgang->rent_end || ! $mietvorgang->items()->exists()) {
            return;
        }

        $mietvorgang->loadMissing(['supplier', 'items.productions']);

        $lines = [
            ['label' => 'Vermieter', 'value' => self::escape($mietvorgang->supplier?->bezeichnung ?? 'unbekannt')],
            ['label' => 'Zeitraum', 'value' => $mietvorgang->rent_start->format('d.m.Y').' – '.$mietvorgang->rent_end->format('d.m.Y')],
            ['label' => 'Transportart Hinweg', 'value' => self::escape($mietvorgang->transport_type_start ?: '–')],
            ['label' => 'Transportart Rückweg', 'value' => self::escape($mietvorgang->transport_type_end ?: '–')],
            ['label' => 'Geräte', 'value' => $this->escapedList($mietvorgang->items->pluck('bezeichnung'))],
        ];

        $productions = $mietvorgang->relatedProductions();
        if ($productions->isNotEmpty()) {
            $lines[] = ['label' => 'Benötigt für', 'value' => $this->escapedList($productions->pluck('bezeichnung'))];
        }

        $lines[] = ['label' => 'Link', 'value' => '<'.route('mietvorgaenge.show', $mietvorgang).'|Zum Vorgang in der App>'];

        $startLabel = $mietvorgang->transportActionLabel('start');
        $endLabel = $mietvorgang->transportActionLabel('end');

        $statuses = [
            $this->statusEntry('mietvorgang', 'start', $startLabel, 'Als '.mb_strtolower($startLabel).' markieren', $mietvorgang->transport_start_confirmed_at, $mietvorgang->transportStartConfirmedBy),
            $this->statusEntry('mietvorgang', 'kontrolliert', 'Geprüft', 'Geprüft', $mietvorgang->kontrolliert_confirmed_at, $mietvorgang->kontrolliertConfirmedBy),
            $this->statusEntry('mietvorgang', 'bereit_zur_rueckgabe', 'Zur Rückgabe fertig', 'Zur Rückgabe fertig', $mietvorgang->bereit_zur_rueckgabe_confirmed_at, $mietvorgang->bereitZurRueckgabeConfirmedBy),
            $this->statusEntry('mietvorgang', 'end', $endLabel, 'Als '.mb_strtolower($endLabel).' markieren', $mietvorgang->transport_end_confirmed_at, $mietvorgang->transportEndConfirmedBy),
        ];

        $this->render($mietvorgang, '📥 MIETE — '.self::escape($mietvorgang->bezeichnung ?? $mietvorgang->supplier?->bezeichnung ?? 'unbekannt'), $lines, $statuses, $mietvorgang->isComplete(), 'slack_reminder_channel');
    }

    public function syncVermietvorgang(Vermietvorgang $vermietvorgang): void
    {
        if (! $vermietvorgang->mieter_id || ! $vermietvorgang->rent_start || ! $vermietvorgang->rent_end || ! $vermietvorgang->items()->exists()) {
            return;
        }

        $vermietvorgang->loadMissing(['mieter', 'items.productions']);

        $lines = [
            ['label' => 'Mieter', 'value' => self::escape($vermietvorgang->mieter?->bezeichnung ?? 'unbekannt')],
            ['label' => 'Zeitraum', 'value' => $vermietvorgang->rent_start->format('d.m.Y').' – '.$vermietvorgang->rent_end->format('d.m.Y')],
            ['label' => 'Transportart Hinweg', 'value' => self::escape($vermietvorgang->transport_type_start ?: '–')],
            ['label' => 'Transportart Rückweg', 'value' => self::escape($vermietvorgang->transport_type_end ?: '–')],
            ['label' => 'Geräte', 'value' => $this->escapedList($vermietvorgang->items->pluck('bezeichnung'))],
        ];

        $productions = $vermietvorgang->relatedProductions();
        if ($productions->isNotEmpty()) {
            $lines[] = ['label' => 'Benötigt für', 'value' => $this->escapedList($productions->pluck('bezeichnung'))];
        }

        $lines[] = ['label' => 'Link', 'value' => '<'.route('vermietvorgaenge.show', $vermietvorgang).'|Zum Vorgang in der App>'];

        $startLabel = $vermietvorgang->transportActionLabel('start');
        $endLabel = $vermietvorgang->transportActionLabel('end');

        // Chronologische Reihenfolge: Gerichtet (Vorbereitung) -> Übergeben
        // (raus zum Mieter) -> Angenommen (zurück vom Mieter) -> Geprüft
        // (Abschlusskontrolle) — unabhängig von den internen Feldnamen.
        $statuses = [
            $this->statusEntry('vermietvorgang', 'gerichtet', 'Gerichtet', 'Gerichtet', $vermietvorgang->gerichtet_confirmed_at, $vermietvorgang->gerichtetConfirmedBy),
            $this->statusEntry('vermietvorgang', 'start', $startLabel, 'Als '.mb_strtolower($startLabel).' markieren', $vermietvorgang->transport_start_confirmed_at, $vermietvorgang->transportStartConfirmedBy),
            $this->statusEntry('vermietvorgang', 'end', $endLabel, 'Als '.mb_strtolower($endLabel).' markieren', $vermietvorgang->transport_end_confirmed_at, $vermietvorgang->transportEndConfirmedBy),
            $this->statusEntry('vermietvorgang', 'vollstaendig_zurueck', 'Geprüft', 'Geprüft', $vermietvorgang->vollstaendig_zurueck_confirmed_at, $vermietvorgang->vollstaendigZurueckConfirmedBy),
        ];

        $this->render($vermietvorgang, '📤 VERMIETUNG — '.self::escape($vermietvorgang->bezeichnung ?? $vermietvorgang->mieter?->bezeichnung ?? 'unbekannt'), $lines, $statuses, $vermietvorgang->isComplete(), 'slack_reminder_channel');
    }

    /**
     * Hält die Slack-Nachricht einer Produktion aktuell: Stammdaten,
     * Zuordnungsstatus-Ampel (aus dem VB-Protokoll-Abgleich), Packstatus und
     * Links zu VB-Protokoll/Packliste/PDF. Anders als bei Mietvorgang/
     * Vermietvorgang gibt es keinen items()-Guard — die Nachricht soll schon
     * beim Anlegen der Produktion erscheinen, bevor Geräte zugeordnet sind.
     */
    public function syncProduction(Production $production): void
    {
        if (! $production->bezeichnung || ! $production->booking_start || ! $production->booking_end) {
            return;
        }

        $production->loadMissing(['vbProtokoll.anforderungen', 'itemPacks', 'packvorgangConfirmedBy']);

        $status = $production->assignmentStatus();
        $ampel = ['grau' => '⚪', 'rot' => '🔴', 'gruen' => '🟢'][$status['level']];

        $gepackt = $production->packedItemIds()->count();
        $gesamt = $production->packlistEntries()->count();
        $packInfo = "{$gepackt}/{$gesamt} Geräte gepackt";
        if ($production->packvorgang_confirmed_at) {
            $by = $production->packvorgangConfirmedBy?->name ? ' von '.self::escape($production->packvorgangConfirmedBy->name) : '';
            $packInfo .= " — ✅ abgeschlossen{$by} am {$production->packvorgang_confirmed_at->format('d.m.Y H:i')} Uhr";
        }

        $vbProtokollLink = $production->vbProtokoll
            ? '<'.route('vb-protokoll.show', $production).'|VB-Protokoll ansehen>'
            : '<'.route('vb-protokoll.create', $production).'|VB-Protokoll anlegen>';

        $lines = [
            ['label' => 'Zeitraum', 'value' => Carbon::parse($production->booking_start)->format('d.m.Y').' – '.Carbon::parse($production->booking_end)->format('d.m.Y')],
            ['label' => 'Kunde', 'value' => self::escape($production->vbProtokoll?->kunde ?: '–')],
            ['label' => 'Ort', 'value' => self::escape($production->vbProtokoll?->produktionsort ?: '–')],
            ['label' => 'Zuordnungsstatus', 'value' => "{$ampel} {$status['label']}"],
            ['label' => 'Packstatus', 'value' => $packInfo],
            ['label' => 'VB-Protokoll', 'value' => $vbProtokollLink],
            ['label' => 'Packliste', 'value' => '<'.route('packvorgang.show', $production).'|Packliste ansehen> · <'.route('packvorgang.pdf', $production).'|als PDF>'],
            ['label' => 'Link', 'value' => '<'.route('productions.show', $production).'|Zur Produktion in der App>'],
        ];

        $packStatus = $this->statusEntry('production', 'packvorgang', 'Packvorgang abgeschlossen', 'Packvorgang abschließen', $production->packvorgang_confirmed_at, $production->packvorgangConfirmedBy);
        $packStatus['confirmDialog'] = $gepackt < $gesamt ? [
            'title' => 'Noch nicht vollständig gepackt',
            'text' => "Es sind erst {$gepackt}/{$gesamt} Geräte als gepackt markiert. Trotzdem abschließen?",
            'confirm' => 'Abschließen',
            'deny' => 'Abbrechen',
        ] : null;
        $statuses = [$packStatus];

        $this->render($production, '🎬 PRODUKTION — '.self::escape($production->bezeichnung), $lines, $statuses, $production->isComplete(), 'slack_production_channel');
    }

    /**
     * Postet einen Reminder-Ping als Thread-Antwort auf die bestehende
     * Vorgangs-Nachricht, statt eine neue Top-Level-Nachricht anzulegen — die
     * Hauptnachricht bleibt die einzige Quelle der Wahrheit für den Status.
     */
    public function threadReply(Mietvorgang|Vermietvorgang|Production $vorgang, string $text): void
    {
        $token = config('services.slack.bot_token');

        if (! $token || ! $vorgang->slack_channel || ! $vorgang->slack_message_ts || ! $this->slackEnabledFor($vorgang)) {
            return;
        }

        $response = $this->slackApi($token, 'chat.postMessage', [
            'channel' => $vorgang->slack_channel,
            'thread_ts' => $vorgang->slack_message_ts,
            'text' => $text,
        ]);

        if ($response && ! $response->json('ok')) {
            Log::warning('Slack-Thread-Reply konnte nicht gesendet werden: '.$response->json('error'));
        }
    }

    /**
     * Ersetzt die Nachricht 48h nach Vorgangsabschluss durch eine einzeilige
     * Kurzfassung ("✅ {Bezeichnung} abgeschlossen.") ohne Details/Buttons —
     * hält den Kanal aufgeräumt, ohne die Nachricht ganz zu löschen. Wird
     * einmalig ausgeführt (slack_compacted_at markiert das danach).
     */
    public function compactIfDue(Mietvorgang|Vermietvorgang|Production $vorgang): void
    {
        if (! $vorgang->slack_message_ts || $vorgang->slack_compacted_at) {
            return;
        }

        $completedAt = $vorgang->completedAt();

        if (! $completedAt || $completedAt->greaterThan(now()->subHours(48))) {
            return;
        }

        $token = config('services.slack.bot_token');

        if (! $token || ! $this->slackEnabledFor($vorgang)) {
            return;
        }

        $label = match (true) {
            $vorgang instanceof Mietvorgang => 'MIETE — '.self::escape($vorgang->bezeichnung ?? $vorgang->supplier?->bezeichnung ?? 'unbekannt'),
            $vorgang instanceof Vermietvorgang => 'VERMIETUNG — '.self::escape($vorgang->bezeichnung ?? $vorgang->mieter?->bezeichnung ?? 'unbekannt'),
            default => 'PRODUKTION — '.self::escape($vorgang->bezeichnung ?? 'unbekannt'),
        };

        $url = match (true) {
            $vorgang instanceof Mietvorgang => route('mietvorgaenge.show', $vorgang),
            $vorgang instanceof Vermietvorgang => route('vermietvorgaenge.show', $vorgang),
            default => route('productions.show', $vorgang),
        };

        $text = "✅ {$label} abgeschlossen. <{$url}|Zum Vorgang>";

        $response = $this->slackApi($token, 'chat.update', [
            'channel' => $vorgang->slack_channel,
            'ts' => $vorgang->slack_message_ts,
            'text' => $text,
            'blocks' => [
                ['type' => 'section', 'text' => ['type' => 'mrkdwn', 'text' => $text]],
            ],
        ]);

        if (! $response?->json('ok')) {
            if ($response) {
                Log::warning('Slack-Nachricht konnte nicht kompaktiert werden: '.$response->json('error'));
            }

            return;
        }

        $vorgang->forceFill(['slack_compacted_at' => now()])->saveQuietly();
    }

    /**
     * Globaler Ein-/Ausschalter je Vorgangsart (Settings-Seite), unabhängig
     * vom hinterlegten Ziel-Kanal. Ohne gespeicherte Einstellung gilt "an",
     * damit bestehende Installationen ohne Konfiguration unverändert weiter
     * senden.
     */
    private function slackEnabledFor(Mietvorgang|Vermietvorgang|Production $vorgang): bool
    {
        $key = $vorgang instanceof Production ? 'slack_production_enabled' : 'slack_reminder_enabled';

        return Setting::get($key, '1') === '1';
    }

    /**
     * Ruft die Slack-Web-API mit kurzem Timeout auf. Ein Netzwerkfehler oder
     * Slack-Ausfall wird nur geloggt (null zurück) — die Geschäftsaktion, die
     * den Sync ausgelöst hat, ist dann längst committed und darf nicht mit
     * einem 500er enden.
     */
    private function slackApi(string $token, string $method, array $payload): ?Response
    {
        try {
            return Http::withToken($token)->timeout(5)->post("https://slack.com/api/{$method}", $payload);
        } catch (ConnectionException $e) {
            Log::warning("Slack {$method} nicht erreichbar: ".$e->getMessage());

            return null;
        }
    }

    /** Kommagetrennte, maskierte Liste; "–" wenn leer. */
    private function escapedList(Collection $values): string
    {
        return $values->map(fn ($v) => self::escape($v))->implode(', ') ?: '–';
    }

    /**
     * @return array{action_id: string, label: string, buttonLabel: string, done: bool, confirmedAt: mixed, confirmedByName: ?string}
     */
    private function statusEntry(string $kind, string $subtype, string $label, string $buttonLabel, mixed $confirmedAt, mixed $confirmedBy): array
    {
        return [
            'action_id' => "confirm:{$kind}:{$subtype}",
            'label' => $label,
            'buttonLabel' => $buttonLabel,
            'done' => $confirmedAt !== null,
            'confirmedAt' => $confirmedAt,
            'confirmedByName' => $confirmedBy?->name ? self::escape($confirmedBy->name) : null,
        ];
    }

    /**
     * Baut die Nachricht komplett aus dem aktuellen Zustand neu auf und
     * postet sie (erstmalig) oder überschreibt die bestehende per
     * chat.update — kein inkrementelles Block-Patching mehr.
     *
     * @param  array<int, array{label: string, value: string}>  $lines
     * @param  array<int, array{action_id: string, label: string, buttonLabel: string, done: bool, confirmedAt: mixed, confirmedByName: ?string}>  $statuses
     */
    private function render(Mietvorgang|Vermietvorgang|Production $vorgang, string $headline, array $lines, array $statuses, bool $complete, string $channelSettingKey): void
    {
        $token = config('services.slack.bot_token');
        $configKey = $channelSettingKey === 'slack_production_channel' ? 'production_channel' : 'reminder_channel';
        $channel = $vorgang->slack_channel ?: Setting::get($channelSettingKey) ?: config("services.slack.{$configKey}");

        if (! $token || ! $channel || ! $this->slackEnabledFor($vorgang)) {
            return;
        }

        $infoText = collect($lines)
            ->map(fn (array $line) => "*{$line['label']}:* {$line['value']}")
            ->implode("\n");

        $statusText = collect($statuses)
            ->map(function (array $status) {
                if (! $status['done']) {
                    return "⬜ {$status['label']}";
                }

                $by = $status['confirmedByName'] ? " von {$status['confirmedByName']}" : '';
                $at = $status['confirmedAt']?->format('d.m.Y H:i');

                return "✅ {$status['label']}{$by} am {$at} Uhr";
            })
            ->implode("\n");

        if ($complete) {
            $statusText .= "\n\n*✅ Vorgang abgeschlossen.*";
        }

        $blocks = [
            [
                'type' => 'section',
                'text' => ['type' => 'mrkdwn', 'text' => "*{$headline}*\n{$infoText}"],
            ],
            [
                'type' => 'section',
                'text' => ['type' => 'mrkdwn', 'text' => $statusText],
            ],
        ];

        if (! $complete) {
            $buttons = collect($statuses)
                ->reject(fn (array $status) => $status['done'])
                ->map(function (array $status) use ($vorgang) {
                    $button = [
                        'type' => 'button',
                        'text' => ['type' => 'plain_text', 'text' => $status['buttonLabel']],
                        'action_id' => $status['action_id'],
                        'value' => (string) $vorgang->id,
                        'style' => 'primary',
                    ];

                    if (! empty($status['confirmDialog'])) {
                        $dialog = $status['confirmDialog'];
                        $button['confirm'] = [
                            'title' => ['type' => 'plain_text', 'text' => $dialog['title']],
                            'text' => ['type' => 'plain_text', 'text' => $dialog['text']],
                            'confirm' => ['type' => 'plain_text', 'text' => $dialog['confirm']],
                            'deny' => ['type' => 'plain_text', 'text' => $dialog['deny']],
                        ];
                    }

                    return $button;
                })
                ->values()
                ->all();

            if ($buttons) {
                $blocks[] = ['type' => 'actions', 'elements' => $buttons];
            }
        }

        $payload = ['channel' => $channel, 'text' => $headline, 'blocks' => $blocks];

        if ($vorgang->slack_message_ts) {
            $response = $this->slackApi($token, 'chat.update', $payload + ['ts' => $vorgang->slack_message_ts]);

            if (! $response || $response->json('ok')) {
                return;
            }

            if ($response->json('error') !== 'message_not_found') {
                Log::warning('Slack-Nachricht konnte nicht aktualisiert werden: '.$response->json('error'));

                return;
            }

            // Nachricht wurde in Slack manuell gelöscht -> defensiv neu anlegen.
        }

        $response = $this->slackApi($token, 'chat.postMessage', $payload);

        if (! $response?->json('ok')) {
            if ($response) {
                Log::warning('Slack-Nachricht konnte nicht gepostet werden: '.$response->json('error'));
            }

            return;
        }

        // chat.update akzeptiert (anders als chat.postMessage) keinen
        // Kanalnamen wie "#kanal" — deshalb die von Slack aufgelöste
        // Kanal-ID aus der Antwort speichern, nicht den Konfigurationswert.
        $vorgang->forceFill([
            'slack_channel' => $response->json('channel') ?: $channel,
            'slack_message_ts' => $response->json('ts'),
        ])->saveQuietly();
    }
}
