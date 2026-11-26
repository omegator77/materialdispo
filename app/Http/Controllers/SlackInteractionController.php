<?php

namespace App\Http\Controllers;

use App\Models\Bestellung;
use App\Models\Mietvorgang;
use App\Models\Production;
use App\Models\User;
use App\Models\Vermietvorgang;
use App\Services\BestellungModals;
use App\Services\SlackBestellungSync;
use App\Services\SlackUserResolver;
use App\Services\SlackVorgangSync;
use App\Support\SlackSignature;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\CauserResolver;

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

    public const DENIED_TEXT = '🚫 Keine Berechtigung: Dein Slack-Konto ist keinem App-Benutzer mit Schreibrechten zugeordnet (Abgleich über die E-Mail-Adresse).';

    private const NOT_ACCOUNTING_TEXT = '🚫 Keine Berechtigung: Nur die Buchhaltung darf Bestellungen als bezahlt & abgelegt markieren.';

    public function __construct(private SlackVorgangSync $slack) {}

    public function handle(Request $request)
    {
        $this->verifySignature($request);

        $payload = json_decode($request->input('payload', '{}'), true);

        if (($payload['type'] ?? null) === 'view_submission') {
            return $this->handleViewSubmission($payload);
        }

        $actionId = $payload['actions'][0]['action_id'] ?? null;
        $responseUrl = $payload['response_url'] ?? null;

        if ($actionId && str_starts_with($actionId, 'bestellung:')) {
            $this->handleBestellungAction($actionId, $payload, $responseUrl);

            return response()->noContent();
        }

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
     * Knöpfe der Bestell-Nachricht. Jede Aktion prüft zuerst die Berechtigung;
     * geändert wird nur über die Modellmethoden (gleiche Regeln wie in der App).
     */
    private function handleBestellungAction(string $actionId, array $payload, ?string $responseUrl): void
    {
        $user = app(SlackUserResolver::class)->resolve($payload['user']['id'] ?? null);

        if (! $user || ! $user->isUser()) {
            $this->replyEphemeralSafely($responseUrl, self::DENIED_TEXT);

            return;
        }

        if ($actionId === 'bestellung:abgelegt' && ! $user->ist_buchhaltung) {
            $this->replyEphemeralSafely($responseUrl, self::NOT_ACCOUNTING_TEXT);

            return;
        }

        $bestellung = Bestellung::find($payload['actions'][0]['value'] ?? null);

        if (! $bestellung) {
            $this->replyEphemeralSafely($responseUrl, '⚠️ Bestellung wurde nicht mehr gefunden.');

            return;
        }

        switch ($actionId) {
            case 'bestellung:sendung':
                $this->openView($payload['trigger_id'] ?? null, BestellungModals::sendung($bestellung), $responseUrl);

                return;

            case 'bestellung:angekommen':
                $this->openView($payload['trigger_id'] ?? null, BestellungModals::angekommen($bestellung), $responseUrl);

                return;

            case 'bestellung:rechnung':
                $neu = $bestellung->markRechnungVorhanden($user);

                // Sync läuft immer (repariert veraltete Nachrichten); die
                // Buchhaltung wird nur beim tatsächlichen Stufenwechsel angepingt.
                dispatch(function () use ($bestellung, $neu) {
                    $sync = app(SlackBestellungSync::class);
                    $sync->sync($bestellung);

                    if ($neu) {
                        $sync->pingBuchhaltung($bestellung->fresh());
                    }
                })->afterResponse();

                return;

            case 'bestellung:abgelegt':
                $bestellung->markAbgelegt($user);
                $this->syncAfterResponse($bestellung);

                return;
        }
    }

    private function handleViewSubmission(array $payload)
    {
        $view = $payload['view'] ?? [];
        $callbackId = $view['callback_id'] ?? null;

        if (! in_array($callbackId, [BestellungModals::CALLBACK_NEU, BestellungModals::CALLBACK_SENDUNG, BestellungModals::CALLBACK_ANGEKOMMEN], true)) {
            return response()->noContent();
        }

        $firstField = match ($callbackId) {
            BestellungModals::CALLBACK_NEU => 'bestellt_bei',
            BestellungModals::CALLBACK_ANGEKOMMEN => 'notiz',
            default => 'versanddienstleister',
        };
        $user = app(SlackUserResolver::class)->resolve($payload['user']['id'] ?? null);

        if (! $user || ! $user->isUser()) {
            return $this->errors([$firstField => self::DENIED_TEXT]);
        }

        return match ($callbackId) {
            BestellungModals::CALLBACK_NEU => $this->submitNeu($view, $user),
            BestellungModals::CALLBACK_ANGEKOMMEN => $this->submitAngekommen($view, $user),
            default => $this->submitSendung($view, $user),
        };
    }

    private function submitNeu(array $view, User $user)
    {
        $data = [];
        $errors = [];

        foreach (['bestellt_bei' => 255, 'bestellt_was' => 2000, 'bezahlt_mit' => 255] as $field => $max) {
            $value = $this->fieldValue($view, $field);

            if ($value === '') {
                $errors[$field] = 'Bitte ausfüllen.';
            } elseif (mb_strlen($value) > $max) {
                $errors[$field] = "Maximal {$max} Zeichen erlaubt.";
            }

            $data[$field] = $value;
        }

        if ($errors) {
            return $this->errors($errors);
        }

        $viewId = $view['id'] ?? null;

        // Slack-Wiederholung desselben Formulars: nichts neu anlegen.
        if ($viewId && Bestellung::where('slack_view_id', $viewId)->exists()) {
            return response('', 200);
        }

        try {
            $resolver = app(CauserResolver::class);
            $resolver->setCauser($user);

            try {
                $bestellung = Bestellung::create($data + [
                    'bestellt_am' => now(),
                    'created_by' => $user->id,
                    'slack_view_id' => $viewId,
                ]);
            } finally {
                $resolver->setCauser(null);
            }
        } catch (UniqueConstraintViolationException) {
            return response('', 200);
        }

        $this->syncAfterResponse($bestellung);

        if (! config('services.slack.bot_token') || ! SlackBestellungSync::channelFor($bestellung)) {
            return response()->json([
                'response_action' => 'update',
                'view' => [
                    'type' => 'modal',
                    'title' => ['type' => 'plain_text', 'text' => 'Bestellung gespeichert'],
                    'close' => ['type' => 'plain_text', 'text' => 'Schließen'],
                    'blocks' => [['type' => 'section', 'text' => ['type' => 'mrkdwn', 'text' => 'Die Bestellung ist gespeichert, aber es ist kein Bestell-Kanal eingestellt. Bitte in den App-Einstellungen den Kanal eintragen; danach lässt sich die Nachricht über „In Slack neu senden“ nachholen.']]],
                ],
            ]);
        }

        return response('', 200);
    }

    private function submitSendung(array $view, User $user)
    {
        $bestellung = Bestellung::find($view['private_metadata'] ?? null);

        if (! $bestellung) {
            return $this->errors(['sendungsnummer' => 'Bestellung wurde nicht mehr gefunden.']);
        }

        $carrier = $view['state']['values']['versanddienstleister']['versanddienstleister']['selected_option']['value'] ?? '';
        $nummer = $this->fieldValue($view, 'sendungsnummer');
        $errors = [];

        if (! array_key_exists($carrier, Bestellung::CARRIERS)) {
            $errors['versanddienstleister'] = 'Bitte einen Versanddienstleister wählen.';
        }

        if ($nummer === '') {
            $errors['sendungsnummer'] = 'Bitte ausfüllen.';
        } elseif (mb_strlen($nummer) > 100) {
            $errors['sendungsnummer'] = 'Maximal 100 Zeichen erlaubt.';
        }

        if ($errors) {
            return $this->errors($errors);
        }

        // Das automatische "updated"-Log entfällt; stattdessen ein lesbarer Eintrag.
        activity()->withoutLogs(fn () => $bestellung->update([
            'versanddienstleister' => $carrier,
            'sendungsnummer' => $nummer,
        ]));

        activity('bestellung')
            ->performedOn($bestellung)
            ->causedBy($user)
            ->event('sendung_eingetragen')
            ->withProperties(['attributes' => ['versanddienstleister' => $carrier, 'sendungsnummer' => $nummer]])
            ->log('Bestellung "'.$bestellung->bestellt_bei.'": Sendung eingetragen ('.Bestellung::CARRIERS[$carrier].' '.$nummer.')');

        $this->syncAfterResponse($bestellung);

        return response('', 200);
    }

    private function submitAngekommen(array $view, User $user)
    {
        $bestellung = Bestellung::find($view['private_metadata'] ?? null);

        if (! $bestellung) {
            return $this->errors(['notiz' => 'Bestellung wurde nicht mehr gefunden.']);
        }

        $notiz = $this->fieldValue($view, 'notiz');

        if (mb_strlen($notiz) > 255) {
            return $this->errors(['notiz' => 'Maximal 255 Zeichen erlaubt.']);
        }

        $bestellung->markAngekommen($user, $notiz !== '' ? $notiz : null);

        // Sync läuft immer (repariert veraltete Nachrichten), auch bei bereits gesetzter Ankunft.
        $this->syncAfterResponse($bestellung);

        return response('', 200);
    }

    /** Getrimmter Wert eines einfachen Eingabefeldes (Block-ID = Action-ID = Feldname). */
    private function fieldValue(array $view, string $field): string
    {
        $value = $view['state']['values'][$field][$field]['value'] ?? '';

        return is_string($value) ? trim($value) : '';
    }

    private function errors(array $errors)
    {
        return response()->json(['response_action' => 'errors', 'errors' => $errors]);
    }

    /** Die Bestell-Nachricht erst nach der Antwort an Slack (3-Sekunden-Limit) posten/aktualisieren. */
    private function syncAfterResponse(Bestellung $bestellung): void
    {
        dispatch(fn () => app(SlackBestellungSync::class)->sync($bestellung))->afterResponse();
    }

    private function openView(?string $triggerId, array $view, ?string $responseUrl): void
    {
        try {
            $response = Http::withToken(config('services.slack.bot_token'))
                ->timeout(5)
                ->post('https://slack.com/api/views.open', ['trigger_id' => $triggerId, 'view' => $view]);

            if ($response->json('ok')) {
                return;
            }

            Log::warning('Slack views.open fehlgeschlagen: '.$response->json('error'));
        } catch (ConnectionException $e) {
            Log::warning('Slack views.open nicht erreichbar: '.$e->getMessage());
        }

        $this->replyEphemeralSafely($responseUrl, 'Formular konnte nicht geöffnet werden.');
    }

    private function replyEphemeralSafely(?string $responseUrl, string $text): void
    {
        try {
            $this->replyEphemeral($responseUrl, $text);
        } catch (ConnectionException $e) {
            Log::warning('Slack response_url nicht erreichbar: '.$e->getMessage());
        }
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
