<?php

namespace App\Http\Controllers;

use App\Models\Mietvorgang;
use App\Models\User;
use App\Models\Vermietvorgang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SlackInteractionController extends Controller
{
    /**
     * Feldpräfix je Subtype (wird zu "{prefix}_confirmed_at"/"_by").
     */
    private const FIELD_PREFIXES = [
        'start' => 'transport_start',
        'end' => 'transport_end',
        'gerichtet' => 'gerichtet',
        'kontrolliert' => 'kontrolliert',
    ];

    private const LABELS = [
        'start' => 'Transport (Hinweg)',
        'end' => 'Transport (Rückweg)',
        'gerichtet' => 'Gerichtet',
        'kontrolliert' => 'Entgegengenommen und kontrolliert',
    ];

    /**
     * Welche Subtypes für welchen Vorgangs-Typ gültig sind — "gerichtet" nur
     * für Vermietvorgang, "kontrolliert" nur für Mietvorgang.
     */
    private const VALID_SUBTYPES = [
        'mietvorgang' => ['start', 'end', 'kontrolliert'],
        'vermietvorgang' => ['start', 'end', 'gerichtet'],
    ];

    public function handle(Request $request)
    {
        $this->verifySignature($request);

        $payload = json_decode($request->input('payload', '{}'), true);

        $actionId = $payload['actions'][0]['action_id'] ?? null;
        $responseUrl = $payload['response_url'] ?? null;

        if ($actionId && str_starts_with($actionId, 'confirm:')) {
            $this->handleConfirm($actionId, $payload, $responseUrl);

            return response()->noContent();
        }

        $user = $payload['user']['username'] ?? 'unbekannt';
        Log::info("Slack-Interaction empfangen: action={$actionId}, user={$user}");

        if ($responseUrl) {
            Http::post($responseUrl, [
                'text' => "✅ Button-Klick von {$user} angekommen (action_id: {$actionId}). Round-Trip funktioniert!",
                'replace_original' => false,
            ]);
        }

        return response()->noContent();
    }

    /**
     * Behandelt Klicks auf einen Bestätigungs-Button aus einer
     * Transport-Erinnerung — setzt exakt dasselbe Feld wie die
     * entsprechende confirm*()-Action in MietvorgangController/
     * VermietvorgangController.
     */
    private function handleConfirm(string $actionId, array $payload, ?string $responseUrl): void
    {
        [, $kind, $subtype] = explode(':', $actionId);

        if (! in_array($subtype, self::VALID_SUBTYPES[$kind] ?? [], true)) {
            return;
        }

        $vorgangId = $payload['actions'][0]['value'] ?? null;
        $vorgang = $kind === 'mietvorgang' ? Mietvorgang::find($vorgangId) : Vermietvorgang::find($vorgangId);

        if (! $vorgang) {
            $this->replaceMessage($responseUrl, '⚠️ Vorgang wurde nicht mehr gefunden.');

            return;
        }

        $slackUserName = $payload['user']['username'] ?? 'jemand';
        $confirmedByUserId = $this->resolveAppUserId($payload['user']['id'] ?? null);
        $causer = $confirmedByUserId ? User::find($confirmedByUserId) : null;
        $field = self::FIELD_PREFIXES[$subtype];
        $label = self::LABELS[$subtype];

        $vorgang->update([
            "{$field}_confirmed_at" => now(),
            "{$field}_confirmed_by" => $confirmedByUserId,
        ]);

        $description = "{$label} über Slack als geklärt markiert";
        if (! $causer) {
            $description .= " (Slack-Nutzer: {$slackUserName}, keine App-Zuordnung gefunden)";
        }

        activity($kind)
            ->performedOn($vorgang)
            ->causedBy($causer)
            ->event('confirmed')
            ->log($description);

        $this->replaceMessage($responseUrl, "✅ {$label} bestätigt von {$slackUserName} am ".now()->format('d.m.Y H:i'));
    }

    /**
     * Ordnet den klickenden Slack-Nutzer per Profil-E-Mail einem App-Nutzer
     * zu (für "confirmed_by"). Kein Treffer oder API-Fehler → null, die
     * Bestätigung läuft trotzdem durch.
     */
    private function resolveAppUserId(?string $slackUserId): ?int
    {
        if (! $slackUserId) {
            return null;
        }

        $response = Http::withToken(config('services.slack.bot_token'))
            ->get('https://slack.com/api/users.info', ['user' => $slackUserId]);

        $email = $response->json('user.profile.email');

        return $email ? User::where('email', $email)->value('id') : null;
    }

    private function replaceMessage(?string $responseUrl, string $text): void
    {
        if (! $responseUrl) {
            return;
        }

        Http::post($responseUrl, [
            'text' => $text,
            'replace_original' => true,
        ]);
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
