<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SlackInteractionController extends Controller
{
    public function handle(Request $request)
    {
        $this->verifySignature($request);

        $payload = json_decode($request->input('payload', '{}'), true);

        $action = $payload['actions'][0]['action_id'] ?? null;
        $user = $payload['user']['username'] ?? 'unbekannt';
        $responseUrl = $payload['response_url'] ?? null;

        Log::info("Slack-Interaction empfangen: action={$action}, user={$user}");

        if ($responseUrl) {
            Http::post($responseUrl, [
                'text' => "✅ Button-Klick von {$user} angekommen (action_id: {$action}). Round-Trip funktioniert!",
                'replace_original' => false,
            ]);
        }

        return response()->noContent();
    }

    private function verifySignature(Request $request): void
    {
        $secret = config('services.slack.signing_secret');
        $timestamp = $request->header('X-Slack-Request-Timestamp');
        $signature = $request->header('X-Slack-Signature');

        abort_if(! $secret || ! $timestamp || ! $signature, 401, 'Fehlende Slack-Signatur.');
        abort_if(abs(time() - (int) $timestamp) > 300, 400, 'Zeitstempel zu alt.');

        $baseString = "v0:{$timestamp}:{$request->getContent()}";
        $expected = 'v0='.hash_hmac('sha256', $baseString, $secret);

        abort_unless(hash_equals($expected, (string) $signature), 401, 'Ungültige Slack-Signatur.');
    }
}
