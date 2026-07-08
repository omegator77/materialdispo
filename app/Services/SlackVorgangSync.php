<?php

namespace App\Services;

use App\Models\Mietvorgang;
use App\Models\Setting;
use App\Models\Vermietvorgang;
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
    public function syncMietvorgang(Mietvorgang $mietvorgang): void
    {
        if (! $mietvorgang->suppliers_id || ! $mietvorgang->rent_start || ! $mietvorgang->rent_end || ! $mietvorgang->items()->exists()) {
            return;
        }

        $mietvorgang->loadMissing(['supplier', 'items.productions']);

        $lines = [
            ['label' => 'Vermieter', 'value' => $mietvorgang->supplier?->bezeichnung ?? 'unbekannt'],
            ['label' => 'Zeitraum', 'value' => $mietvorgang->rent_start->format('d.m.Y').' – '.$mietvorgang->rent_end->format('d.m.Y')],
            ['label' => 'Transportart Hinweg', 'value' => $mietvorgang->transport_type_start ?: '–'],
            ['label' => 'Transportart Rückweg', 'value' => $mietvorgang->transport_type_end ?: '–'],
            ['label' => 'Geräte', 'value' => $mietvorgang->items->pluck('bezeichnung')->implode(', ') ?: '–'],
        ];

        $productions = $mietvorgang->relatedProductions();
        if ($productions->isNotEmpty()) {
            $lines[] = ['label' => 'Benötigt für', 'value' => $productions->pluck('bezeichnung')->implode(', ')];
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

        $this->render($mietvorgang, '📦 '.($mietvorgang->bezeichnung ?? $mietvorgang->supplier?->bezeichnung ?? 'unbekannt'), $lines, $statuses, $mietvorgang->isComplete());
    }

    public function syncVermietvorgang(Vermietvorgang $vermietvorgang): void
    {
        if (! $vermietvorgang->mieter_id || ! $vermietvorgang->rent_start || ! $vermietvorgang->rent_end || ! $vermietvorgang->items()->exists()) {
            return;
        }

        $vermietvorgang->loadMissing(['mieter', 'items.productions']);

        $lines = [
            ['label' => 'Mieter', 'value' => $vermietvorgang->mieter?->bezeichnung ?? 'unbekannt'],
            ['label' => 'Zeitraum', 'value' => $vermietvorgang->rent_start->format('d.m.Y').' – '.$vermietvorgang->rent_end->format('d.m.Y')],
            ['label' => 'Transportart Hinweg', 'value' => $vermietvorgang->transport_type_start ?: '–'],
            ['label' => 'Transportart Rückweg', 'value' => $vermietvorgang->transport_type_end ?: '–'],
            ['label' => 'Geräte', 'value' => $vermietvorgang->items->pluck('bezeichnung')->implode(', ') ?: '–'],
        ];

        $productions = $vermietvorgang->relatedProductions();
        if ($productions->isNotEmpty()) {
            $lines[] = ['label' => 'Benötigt für', 'value' => $productions->pluck('bezeichnung')->implode(', ')];
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

        $this->render($vermietvorgang, '📦 '.($vermietvorgang->bezeichnung ?? $vermietvorgang->mieter?->bezeichnung ?? 'unbekannt'), $lines, $statuses, $vermietvorgang->isComplete());
    }

    /**
     * Postet einen Reminder-Ping als Thread-Antwort auf die bestehende
     * Vorgangs-Nachricht, statt eine neue Top-Level-Nachricht anzulegen — die
     * Hauptnachricht bleibt die einzige Quelle der Wahrheit für den Status.
     */
    public function threadReply(Mietvorgang|Vermietvorgang $vorgang, string $text): void
    {
        $token = config('services.slack.bot_token');

        if (! $token || ! $vorgang->slack_channel || ! $vorgang->slack_message_ts) {
            return;
        }

        $response = Http::withToken($token)->post('https://slack.com/api/chat.postMessage', [
            'channel' => $vorgang->slack_channel,
            'thread_ts' => $vorgang->slack_message_ts,
            'text' => $text,
        ]);

        if (! $response->json('ok')) {
            Log::warning('Slack-Thread-Reply konnte nicht gesendet werden: '.$response->json('error'));
        }
    }

    /**
     * Ersetzt die Nachricht 48h nach Vorgangsabschluss durch eine einzeilige
     * Kurzfassung ("✅ {Bezeichnung} abgeschlossen.") ohne Details/Buttons —
     * hält den Kanal aufgeräumt, ohne die Nachricht ganz zu löschen. Wird
     * einmalig ausgeführt (slack_compacted_at markiert das danach).
     */
    public function compactIfDue(Mietvorgang|Vermietvorgang $vorgang): void
    {
        if (! $vorgang->slack_message_ts || $vorgang->slack_compacted_at) {
            return;
        }

        $completedAt = $vorgang->completedAt();

        if (! $completedAt || $completedAt->greaterThan(now()->subHours(48))) {
            return;
        }

        $token = config('services.slack.bot_token');

        if (! $token) {
            return;
        }

        $label = $vorgang instanceof Mietvorgang
            ? ($vorgang->bezeichnung ?? $vorgang->supplier?->bezeichnung ?? 'unbekannt')
            : ($vorgang->bezeichnung ?? $vorgang->mieter?->bezeichnung ?? 'unbekannt');

        $url = $vorgang instanceof Mietvorgang
            ? route('mietvorgaenge.show', $vorgang)
            : route('vermietvorgaenge.show', $vorgang);

        $text = "✅ {$label} abgeschlossen. <{$url}|Zum Vorgang>";

        $response = Http::withToken($token)->post('https://slack.com/api/chat.update', [
            'channel' => $vorgang->slack_channel,
            'ts' => $vorgang->slack_message_ts,
            'text' => $text,
            'blocks' => [
                ['type' => 'section', 'text' => ['type' => 'mrkdwn', 'text' => $text]],
            ],
        ]);

        if (! $response->json('ok')) {
            Log::warning('Slack-Nachricht konnte nicht kompaktiert werden: '.$response->json('error'));

            return;
        }

        $vorgang->forceFill(['slack_compacted_at' => now()])->saveQuietly();
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
            'confirmedByName' => $confirmedBy?->name,
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
    private function render(Mietvorgang|Vermietvorgang $vorgang, string $headline, array $lines, array $statuses, bool $complete): void
    {
        $token = config('services.slack.bot_token');
        $channel = $vorgang->slack_channel ?: Setting::get('slack_reminder_channel') ?: config('services.slack.reminder_channel');

        if (! $token || ! $channel) {
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
                ->map(fn (array $status) => [
                    'type' => 'button',
                    'text' => ['type' => 'plain_text', 'text' => $status['buttonLabel']],
                    'action_id' => $status['action_id'],
                    'value' => (string) $vorgang->id,
                    'style' => 'primary',
                ])
                ->values()
                ->all();

            if ($buttons) {
                $blocks[] = ['type' => 'actions', 'elements' => $buttons];
            }
        }

        $payload = ['channel' => $channel, 'text' => $headline, 'blocks' => $blocks];

        if ($vorgang->slack_message_ts) {
            $response = Http::withToken($token)->post('https://slack.com/api/chat.update', $payload + ['ts' => $vorgang->slack_message_ts]);

            if ($response->json('ok')) {
                return;
            }

            if ($response->json('error') !== 'message_not_found') {
                Log::warning('Slack-Nachricht konnte nicht aktualisiert werden: '.$response->json('error'));

                return;
            }

            // Nachricht wurde in Slack manuell gelöscht -> defensiv neu anlegen.
        }

        $response = Http::withToken($token)->post('https://slack.com/api/chat.postMessage', $payload);

        if (! $response->json('ok')) {
            Log::warning('Slack-Nachricht konnte nicht gepostet werden: '.$response->json('error'));

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
