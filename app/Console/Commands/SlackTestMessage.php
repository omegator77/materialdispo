<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SlackTestMessage extends Command
{
    protected $signature = 'slack:test-message {channel : Kanal-Name (z.B. #general) oder Kanal-ID}';

    protected $description = 'Sendet eine Testnachricht mit Button an einen Slack-Kanal, um die Bot-Verbindung zu prüfen.';

    public function handle(): int
    {
        $token = config('services.slack.bot_token');

        if (! $token) {
            $this->error('SLACK_BOT_TOKEN ist nicht gesetzt.');

            return self::FAILURE;
        }

        $response = Http::withToken($token)->post('https://slack.com/api/chat.postMessage', [
            'channel' => $this->argument('channel'),
            'text' => 'Testnachricht aus material_dispo',
            'blocks' => [
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => "🧪 *Testnachricht aus material\\_dispo*\nWenn du den Button klickst, sollte innerhalb weniger Sekunden eine Bestätigung hier erscheinen.",
                    ],
                ],
                [
                    'type' => 'actions',
                    'elements' => [
                        [
                            'type' => 'button',
                            'text' => ['type' => 'plain_text', 'text' => 'Testbutton klicken'],
                            'action_id' => 'test_confirm',
                            'value' => 'test-value',
                        ],
                    ],
                ],
            ],
        ]);

        $body = $response->json();

        if (! ($body['ok'] ?? false)) {
            $this->error('Slack-API-Fehler: '.($body['error'] ?? 'unbekannt'));

            return self::FAILURE;
        }

        $this->info('Nachricht gesendet (ts: '.$body['ts'].').');

        return self::SUCCESS;
    }
}
