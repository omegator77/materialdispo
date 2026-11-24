<?php

namespace App\Support;

use Illuminate\Http\Request;

class SlackSignature
{
    /**
     * Prüft die Slack-Request-Signatur (Signing Secret, Zeitstempel max. 300 s
     * alt). Bricht bei Verstoß per abort() mit 400/401 ab.
     */
    public static function verify(Request $request): void
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
