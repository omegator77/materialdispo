<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Ordnet Slack-Nutzer und App-Benutzer über die E-Mail-Adresse einander zu.
 */
class SlackUserResolver
{
    /** Slack-Nutzer -> App-Benutzer. Nur Treffer werden (10 Minuten) gecacht. */
    public function resolve(?string $slackUserId): ?User
    {
        $token = config('services.slack.bot_token');

        if (! $slackUserId || ! $token) {
            return null;
        }

        $cacheKey = 'slack-user-resolver:'.$slackUserId;
        $cachedId = Cache::get($cacheKey);

        if ($cachedId && $user = User::find($cachedId)) {
            return $user;
        }

        try {
            $response = Http::withToken($token)->timeout(5)
                ->get('https://slack.com/api/users.info', ['user' => $slackUserId]);
        } catch (ConnectionException $e) {
            Log::warning('Slack users.info nicht erreichbar: '.$e->getMessage());

            return null;
        }

        $email = $response->json('user.profile.email');
        $user = $email ? User::where('email', $email)->first() : null;

        if ($user) {
            Cache::put($cacheKey, $user->id, now()->addMinutes(10));
        }

        return $user;
    }

    /** App-Benutzer -> Slack-Nutzer-ID (für Erwähnungen); null bei Fehler/kein Treffer. */
    public function slackIdFor(User $user): ?string
    {
        $token = config('services.slack.bot_token');

        if (! $token || ! $user->email) {
            return null;
        }

        try {
            $response = Http::withToken($token)->timeout(5)
                ->get('https://slack.com/api/users.lookupByEmail', ['email' => $user->email]);
        } catch (ConnectionException $e) {
            Log::warning('Slack users.lookupByEmail nicht erreichbar: '.$e->getMessage());

            return null;
        }

        return $response->json('ok') ? ($response->json('user.id') ?: null) : null;
    }
}
