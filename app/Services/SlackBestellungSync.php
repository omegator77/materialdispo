<?php

namespace App\Services;

use App\Models\Bestellung;
use App\Models\Setting;
use App\Models\User;
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
        $channel = self::channelFor($bestellung);

        if (! $token || ! $channel) {
            return;
        }

        $bestellung->loadMissing(['besteller', 'abgelegtVon']);

        $headline = '🛒 BESTELLUNG — '.$this->kurz($bestellung->bestellt_bei, 200);
        $stufe = $bestellung->stufe();

        // Slack begrenzt Section-Texte auf 3000 Zeichen: Langtexte werden vor dem
        // Maskieren gekürzt und liegen in eigenen Abschnitten.
        $kopf = [
            '*Bestellt bei:* '.$this->kurz($bestellung->bestellt_bei, 200),
            '*Bezahlt mit:* '.$this->kurz($bestellung->bezahlt_mit, 200),
        ];

        $details = [
            '*Datum:* '.($bestellung->bestellt_am?->format('d.m.Y H:i') ?? '–'),
            '*Besteller:* '.$this->kurz($bestellung->besteller?->name ?? 'unbekannt', 100),
            '*Stufe:* '.$bestellung->stufeLabel(),
        ];

        if ($bestellung->sendungsnummer) {
            $details[] = '*Sendung:* '.$this->sendungText($bestellung);
        }

        $blocks = [
            ['type' => 'section', 'text' => ['type' => 'mrkdwn', 'text' => "*{$headline}*\n".implode("\n", $kopf)]],
            ['type' => 'section', 'text' => ['type' => 'mrkdwn', 'text' => '*Was:* '.$this->kurz($bestellung->bestellt_was, 1500, ' … (vollständig in der App)')]],
            ['type' => 'section', 'text' => ['type' => 'mrkdwn', 'text' => implode("\n", $details)]],
        ];

        if ($stufe === Bestellung::STUFE_ABGELEGT) {
            $by = $bestellung->abgelegtVon?->name ? ' von '.$this->kurz($bestellung->abgelegtVon->name, 100) : '';
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

    /** @return bool true, wenn Slack die Antwort angenommen hat */
    public function threadReply(Bestellung $bestellung, string $text): bool
    {
        $token = config('services.slack.bot_token');

        if (! $token || ! $bestellung->slack_channel || ! $bestellung->slack_message_ts) {
            return false;
        }

        $response = $this->slackApi($token, 'chat.postMessage', [
            'channel' => $bestellung->slack_channel,
            'thread_ts' => $bestellung->slack_message_ts,
            'text' => $text,
        ]);

        if ($response && ! $response->json('ok')) {
            Log::warning('Slack-Thread-Reply (Bestellung) konnte nicht gesendet werden: '.$response->json('error'));
        }

        return (bool) $response?->json('ok');
    }

    /** Erwähnt die Buchhaltungs-Benutzer (mit Slack-Konto, sonst Klartext) im Thread der Bestellung. */
    public function pingBuchhaltung(Bestellung $bestellung): void
    {
        $this->threadReply($bestellung, $this->buchhaltungMentions().' Rechnung liegt vor: bitte herunterladen, bezahlen und ablegen.');
    }

    /** Erwähnungen (<@U…>) der Buchhaltung mit Slack-Konto, sonst "Buchhaltung:". */
    public function buchhaltungMentions(): string
    {
        $resolver = app(SlackUserResolver::class);
        $mentions = User::buchhaltung()->get()
            ->map(fn (User $u) => $resolver->slackIdFor($u))
            ->filter()
            ->map(fn (string $id) => "<@{$id}>")
            ->implode(' ');

        return $mentions !== '' ? $mentions : 'Buchhaltung:';
    }

    /** Ziel-Kanal: Kanal der Nachricht, sonst Einstellung, sonst Konfiguration. */
    public static function channelFor(?Bestellung $bestellung = null): ?string
    {
        return $bestellung?->slack_channel ?: Setting::get('slack_order_channel') ?: config('services.slack.order_channel') ?: null;
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
            $label = str_replace('|', '¦', $this->kurz($nummer, 100));
            $nummerText = "<{$url}|{$label}>";
        } else {
            $nummerText = $this->kurz($nummer, 100);
        }

        return ($dienst ? SlackVorgangSync::escape($dienst).' ' : '').$nummerText;
    }

    /**
     * Kürzt vor dem Maskieren so, dass der maskierte Text höchstens $max
     * Zeichen lang ist (Entities werden nie zerschnitten).
     */
    private function kurz(?string $text, int $max, string $hinweis = ' …'): string
    {
        $text = (string) $text;

        if (mb_strlen(SlackVorgangSync::escape($text)) <= $max) {
            return SlackVorgangSync::escape($text);
        }

        $rest = $max - mb_strlen(SlackVorgangSync::escape($hinweis));
        $out = '';
        $len = 0;

        foreach (mb_str_split($text) as $char) {
            $add = mb_strlen(SlackVorgangSync::escape($char));
            if ($len + $add > $rest) {
                break;
            }
            $out .= $char;
            $len += $add;
        }

        return SlackVorgangSync::escape($out).SlackVorgangSync::escape($hinweis);
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
