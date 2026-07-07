<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SlackReminderNotifier
{
    /**
     * Postet eine Transport-Erinnerung mit einem oder mehreren
     * Bestätigungs-Buttons in den konfigurierten Slack-Kanal. Slack ist ein
     * optionaler Zusatzkanal zur E-Mail — fehlt Bot-Token oder Kanal, wird
     * einfach nichts gesendet.
     *
     * @param  array<int, array{label: string, value: string}>  $lines
     * @param  array<int, array{action_id: string, label: string}>  $buttons
     */
    public function send(int $vorgangId, string $headline, array $lines, array $buttons): void
    {
        $token = config('services.slack.bot_token');
        $channel = config('services.slack.reminder_channel');

        if (! $token || ! $channel) {
            return;
        }

        $text = collect($lines)
            ->map(fn (array $line) => "*{$line['label']}:* {$line['value']}")
            ->implode("\n");

        $response = Http::withToken($token)->post('https://slack.com/api/chat.postMessage', [
            'channel' => $channel,
            'text' => $headline,
            'blocks' => [
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => "*{$headline}*\n{$text}",
                    ],
                ],
                [
                    'type' => 'actions',
                    'elements' => collect($buttons)->map(fn (array $button) => [
                        'type' => 'button',
                        'text' => ['type' => 'plain_text', 'text' => $button['label']],
                        'action_id' => $button['action_id'],
                        'value' => (string) $vorgangId,
                        'style' => 'primary',
                    ])->all(),
                ],
            ],
        ]);

        if (! $response->json('ok')) {
            Log::warning('Slack-Reminder konnte nicht gesendet werden: '.$response->json('error'));
        }
    }
}
