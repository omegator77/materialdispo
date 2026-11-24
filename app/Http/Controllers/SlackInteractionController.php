<?php

namespace App\Http\Controllers;

use App\Models\Mietvorgang;
use App\Models\Production;
use App\Models\User;
use App\Models\Vermietvorgang;
use App\Services\SlackVorgangSync;
use App\Support\SlackSignature;
use Illuminate\Http\Client\ConnectionException;
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
        'vollstaendig_zurueck' => 'vollstaendig_zurueck',
        'bereit_zur_rueckgabe' => 'bereit_zur_rueckgabe',
        'packvorgang' => 'packvorgang',
    ];

    /**
     * Labels für die richtungsunabhängigen Subtypes. "start"/"end" haben kein
     * festes Label — das hängt von Mietvorgang/Vermietvorgang ab und kommt
     * über $vorgang->transportActionLabel($subtype).
     */
    private const NON_TRANSPORT_LABELS = [
        'gerichtet' => 'Gerichtet',
        'kontrolliert' => 'Entgegengenommen und kontrolliert',
        'vollstaendig_zurueck' => 'Vollständig zurück',
        'bereit_zur_rueckgabe' => 'Bereit zur Rückgabe',
        'packvorgang' => 'Packvorgang abgeschlossen',
    ];

    /**
     * Welche Subtypes für welchen Vorgangs-Typ gültig sind — "gerichtet"/
     * "vollstaendig_zurueck" nur für Vermietvorgang, "kontrolliert"/
     * "bereit_zur_rueckgabe" nur für Mietvorgang.
     */
    private const VALID_SUBTYPES = [
        'mietvorgang' => ['start', 'end', 'kontrolliert', 'bereit_zur_rueckgabe'],
        'vermietvorgang' => ['start', 'end', 'gerichtet', 'vollstaendig_zurueck'],
        'production' => ['packvorgang'],
    ];

    public function __construct(private SlackVorgangSync $slack) {}

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
        $parts = explode(':', $actionId, 3);

        if (count($parts) !== 3) {
            return;
        }

        [, $kind, $subtype] = $parts;

        if (! in_array($subtype, self::VALID_SUBTYPES[$kind] ?? [], true)) {
            return;
        }

        // Dieselbe Schreibberechtigung wie in der App (Rolle admin/user): Der
        // klickende Slack-Nutzer muss per E-Mail einem App-Nutzer zugeordnet
        // sein. Betrachter und Unbekannte im Kanal dürfen nichts bestätigen.
        $causer = $this->resolveAppUser($payload['user']['id'] ?? null);

        if (! $causer || ! $causer->isUser()) {
            $this->replyEphemeral($responseUrl, '🚫 Keine Berechtigung: Dein Slack-Konto ist keinem App-Benutzer mit Schreibrechten zugeordnet (Abgleich über die E-Mail-Adresse).');

            return;
        }

        $vorgangId = $payload['actions'][0]['value'] ?? null;
        $vorgang = match ($kind) {
            'mietvorgang' => Mietvorgang::find($vorgangId),
            'vermietvorgang' => Vermietvorgang::find($vorgangId),
            'production' => Production::find($vorgangId),
        };

        if (! $vorgang) {
            $this->replaceMessage($responseUrl, '⚠️ Vorgang wurde nicht mehr gefunden.');

            return;
        }

        $field = self::FIELD_PREFIXES[$subtype];
        $label = in_array($subtype, ['start', 'end'], true)
            ? $vorgang->transportActionLabel($subtype)
            : self::NON_TRANSPORT_LABELS[$subtype];

        $vorgang->update([
            "{$field}_confirmed_at" => now(),
            "{$field}_confirmed_by" => $causer->id,
        ]);

        activity($kind)
            ->performedOn($vorgang)
            ->causedBy($causer)
            ->event('confirmed')
            ->log("{$label} über Slack als geklärt markiert");

        match ($kind) {
            'mietvorgang' => $this->slack->syncMietvorgang($vorgang),
            'vermietvorgang' => $this->slack->syncVermietvorgang($vorgang),
            'production' => $this->slack->syncProduction($vorgang),
        };
    }

    /**
     * Ordnet den klickenden Slack-Nutzer per Profil-E-Mail einem App-Nutzer
     * zu. Kein Treffer, fehlendes Token oder API-/Netzwerkfehler → null
     * (der Klick wird dann abgelehnt, nicht durchgewunken).
     */
    private function resolveAppUser(?string $slackUserId): ?User
    {
        if (! $slackUserId) {
            return null;
        }

        try {
            $response = Http::withToken(config('services.slack.bot_token'))
                ->timeout(5)
                ->get('https://slack.com/api/users.info', ['user' => $slackUserId]);
        } catch (ConnectionException $e) {
            Log::warning('Slack users.info nicht erreichbar: '.$e->getMessage());

            return null;
        }

        $email = $response->json('user.profile.email');

        return $email ? User::where('email', $email)->first() : null;
    }

    /** Antwort, die nur der klickende Slack-Nutzer sieht. */
    private function replyEphemeral(?string $responseUrl, string $text): void
    {
        if (! $responseUrl) {
            return;
        }

        Http::timeout(5)->post($responseUrl, [
            'response_type' => 'ephemeral',
            'replace_original' => false,
            'text' => $text,
        ]);
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
        SlackSignature::verify($request);
    }
}
