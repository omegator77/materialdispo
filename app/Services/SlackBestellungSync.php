<?php

namespace App\Services;

use App\Models\Bestellung;
use App\Models\Setting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Hält je Bestellung genau eine Slack-Nachricht aktuell. Die Nachricht wird
 * bei jedem Aufruf komplett aus dem Zustand neu gebaut (idempotent): beim
 * ersten Mal gepostet, danach per chat.update überschrieben. Slack-Fehler
 * werden nur geloggt und brechen nie die auslösende Aktion ab.
 */
class SlackBestellungSync
{
    public function sync(Bestellung $bestellung): void
    {
        $token = config('services.slack.bot_token');
        $channel = $this->channelFor($bestellung);

        if (! $token || ! $channel) {
            return;
        }

        $bestellung->loadMissing(['besteller', 'abgelegtVon']);

        $headline = '🛒 BESTELLUNG — '.SlackVorgangSync::escape($bestellung->bestellt_bei);
        $stufe = $bestellung->stufe();

        $lines = [
            '*Bestellt bei:* '.SlackVorgangSync::escape($bestellung->bestellt_bei),
            '*Was:* '.SlackVorgangSync::escape($bestellung->bestellt_was),
            '*Bezahlt mit:* '.SlackVorgangSync::escape($bestellung->bezahlt_mit),
            '*Datum:* '.($bestellung->bestellt_am?->format('d.m.Y H:i') ?? '–'),
            '*Besteller:* '.SlackVorgangSync::escape($bestellung->besteller?->name ?? 'unbekannt'),
            '*Stufe:* '.$bestellung->stufeLabel(),
        ];

        if ($bestellung->sendungsnummer) {
            $lines[] = '*Sendung:* '.$this->sendungText($bestellung);
        }

        $blocks = [
            ['type' => 'section', 'text' => ['type' => 'mrkdwn', 'text' => "*{$headline}*\n".implode("\n", $lines)]],
        ];

        if ($stufe === Bestellung::STUFE_ABGELEGT) {
            $by = $bestellung->abgelegtVon?->name ? ' von '.SlackVorgangSync::escape($bestellung->abgelegtVon->name) : '';
            $blocks[] = ['type' => 'section', 'text' => ['type' => 'mrkdwn', 'text' => "✅ bezahlt & abgelegt{$by} am {$bestellung->abgelegt_at->format('d.m.Y H:i')} Uhr"]];
        } else {
            $buttons = [$this->button('bestellung:sendung', 'Sendung eintragen', $bestellung)];

            if ($stufe === Bestellung::STUFE_RECHNUNG) {
                $buttons[] = $this->button('bestellung:abgelegt', 'Bezahlt & abgelegt', $bestellung);
            } else {
                $buttons[] = $this->button('bestellung:rechnung', 'Rechnung liegt vor', $bestellung);
            }

            $blocks[] = ['type' => 'actions', 'elements' => $buttons];
            $blocks[] = ['type' => 'context', 'elements' => [['type' => 'mrkdwn', 'text' => 'Rechnung als Datei in den Thread dieser Nachricht hochladen']]];
        }

        $payload = ['channel' => $channel, 'text' => $headline, 'blocks' => $blocks];

        if ($bestellung->slack_message_ts) {
            $response = $this->slackApi($token, 'chat.update', $payload + ['ts' => $bestellung->slack_message_ts]);

            if (! $response || $response->json('ok')) {
                return;
            }

            if ($response->json('error') !== 'message_not_found') {
                Log::warning('Slack-Bestellung konnte nicht aktualisiert werden: '.$response->json('error'));

                return;
            }
            // Nachricht in Slack gelöscht -> neu anlegen.
        }

        $response = $this->slackApi($token, 'chat.postMessage', $payload);

        if (! $response?->json('ok')) {
            if ($response) {
                Log::warning('Slack-Bestellung konnte nicht gepostet werden: '.$response->json('error'));
            }

            return;
        }

        $bestellung->forceFill([
            'slack_channel' => $response->json('channel') ?: $channel,
            'slack_message_ts' => $response->json('ts'),
        ])->saveQuietly();
    }

    public function threadReply(Bestellung $bestellung, string $text): void
    {
        $token = config('services.slack.bot_token');

        if (! $token || ! $bestellung->slack_channel || ! $bestellung->slack_message_ts) {
            return;
        }

        $response = $this->slackApi($token, 'chat.postMessage', [
            'channel' => $bestellung->slack_channel,
            'thread_ts' => $bestellung->slack_message_ts,
            'text' => $text,
        ]);

        if ($response && ! $response->json('ok')) {
            Log::warning('Slack-Thread-Reply (Bestellung) konnte nicht gesendet werden: '.$response->json('error'));
        }
    }

    private function channelFor(Bestellung $bestellung): ?string
    {
        return $bestellung->slack_channel ?: Setting::get('slack_order_channel') ?: config('services.slack.order_channel');
    }

    private function sendungText(Bestellung $bestellung): string
    {
        $dienst = Bestellung::CARRIERS[$bestellung->versanddienstleister ?? ''] ?? null;
        $nummer = $bestellung->sendungsnummer;
        $url = $bestellung->trackingUrl();

        if ($url) {
            // URL ist rawurlencoded (kein | oder >); zur Sicherheit trotzdem kodieren.
            $url = str_replace(['|', '>', '<'], ['%7C', '%3E', '%3C'], $url);
            // Label: maskieren und "|" ersetzen, damit der Link nicht aufbricht.
            $label = str_replace('|', '¦', SlackVorgangSync::escape($nummer));
            $nummerText = "<{$url}|{$label}>";
        } else {
            $nummerText = SlackVorgangSync::escape($nummer);
        }

        return ($dienst ? SlackVorgangSync::escape($dienst).' ' : '').$nummerText;
    }

    private function button(string $actionId, string $label, Bestellung $bestellung): array
    {
        return [
            'type' => 'button',
            'text' => ['type' => 'plain_text', 'text' => $label],
            'action_id' => $actionId,
            'value' => (string) $bestellung->id,
        ];
    }

    private function slackApi(string $token, string $method, array $payload): ?Response
    {
        try {
            return Http::withToken($token)->timeout(5)->post("https://slack.com/api/{$method}", $payload);
        } catch (ConnectionException $e) {
            Log::warning("Slack {$method} nicht erreichbar: ".$e->getMessage());

            return null;
        }
    }
}
