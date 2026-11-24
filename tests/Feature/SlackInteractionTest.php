<?php

use App\Models\Bestellung;
use App\Models\Mietvorgang;
use App\Models\Supplier;
use App\Models\User;
use App\Services\BestellungModals;
use Illuminate\Support\Facades\Http;

const SLACK_SECRET = 'test-signing-secret';

function slackPost(object $test, array $payload, ?int $timestamp = null, ?string $signature = null)
{
    $body = 'payload='.urlencode(json_encode($payload));
    $timestamp ??= time();
    $signature ??= 'v0='.hash_hmac('sha256', "v0:{$timestamp}:{$body}", SLACK_SECRET);

    return $test->call('POST', '/slack/interactions', ['payload' => json_encode($payload)], [], [], [
        'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
        'HTTP_X_SLACK_REQUEST_TIMESTAMP' => (string) $timestamp,
        'HTTP_X_SLACK_SIGNATURE' => $signature,
    ], $body);
}

function slackConfirmPayload(Mietvorgang $mv, string $slackUserId = 'U1'): array
{
    return [
        'user' => ['id' => $slackUserId, 'username' => 'klicker'],
        'response_url' => 'https://hooks.slack.test/response',
        'actions' => [['action_id' => 'confirm:mietvorgang:start', 'value' => (string) $mv->id]],
    ];
}

function slackMietvorgang(): Mietvorgang
{
    $supplier = Supplier::create(['bezeichnung' => 'Vermieter']);

    return Mietvorgang::create(['suppliers_id' => $supplier->id, 'rent_start' => '2026-08-01', 'rent_end' => '2026-08-05']);
}

function fakeSlackUser(?string $email): void
{
    Http::fake([
        'slack.com/api/users.info*' => Http::response(['ok' => true, 'user' => ['profile' => ['email' => $email]]]),
        'hooks.slack.test/*' => Http::response('ok'),
        '*' => Http::response(['ok' => true]),
    ]);
}

beforeEach(function () {
    config(['services.slack.signing_secret' => SLACK_SECRET, 'services.slack.bot_token' => 'xoxb-test']);
});

test('Slack-Klick eines App-Benutzers mit Schreibrolle bestätigt den Vorgang', function () {
    $user = User::factory()->create(['role' => 'user', 'email' => 'crew@example.com']);
    fakeSlackUser('crew@example.com');
    $mv = slackMietvorgang();

    slackPost($this, slackConfirmPayload($mv))->assertNoContent();

    $mv->refresh();
    expect($mv->transport_start_confirmed_at)->not->toBeNull()
        ->and($mv->transport_start_confirmed_by)->toBe($user->id);
});

test('Slack-Klick ohne zugeordneten App-Benutzer bestätigt nichts und antwortet nur dem Klickenden', function () {
    fakeSlackUser('unbekannt@example.com');
    $mv = slackMietvorgang();

    slackPost($this, slackConfirmPayload($mv))->assertNoContent();

    expect($mv->fresh()->transport_start_confirmed_at)->toBeNull();
    Http::assertSent(fn ($r) => $r->url() === 'https://hooks.slack.test/response'
        && $r['response_type'] === 'ephemeral'
        && str_contains($r['text'], 'Keine Berechtigung'));
});

test('Slack-Klick eines Viewers bestätigt nichts', function () {
    User::factory()->create(['role' => 'viewer', 'email' => 'viewer@example.com']);
    fakeSlackUser('viewer@example.com');
    $mv = slackMietvorgang();

    slackPost($this, slackConfirmPayload($mv))->assertNoContent();

    expect($mv->fresh()->transport_start_confirmed_at)->toBeNull();
});

test('Slack-Aufruf mit falscher Signatur wird abgelehnt', function () {
    fakeSlackUser('x@example.com');
    $mv = slackMietvorgang();

    slackPost($this, slackConfirmPayload($mv), signature: 'v0=falsch')->assertUnauthorized();

    expect($mv->fresh()->transport_start_confirmed_at)->toBeNull();
});

test('Slack-Aufruf mit altem Zeitstempel wird abgelehnt', function () {
    fakeSlackUser('x@example.com');

    slackPost($this, slackConfirmPayload(slackMietvorgang()), timestamp: time() - 3600)->assertStatus(400);
});

test('Slack-Aufruf ohne konfiguriertes Signing-Secret wird abgelehnt', function () {
    config(['services.slack.signing_secret' => null]);

    slackPost($this, slackConfirmPayload(slackMietvorgang()))->assertUnauthorized();
});

test('Eine unvollständige action_id führt nicht zu einem Serverfehler', function () {
    fakeSlackUser('x@example.com');
    $payload = slackConfirmPayload(slackMietvorgang());
    $payload['actions'][0]['action_id'] = 'confirm:mietvorgang';

    slackPost($this, $payload)->assertNoContent();
});

test('Modal-Eingaben haben max_length', function () {
    $neu = BestellungModals::neu();
    $max = collect($neu['blocks'])->mapWithKeys(fn ($b) => [$b['block_id'] => $b['element']['max_length'] ?? null])->all();
    expect($max)->toBe(['bestellt_bei' => 255, 'bestellt_was' => 2000, 'bezahlt_mit' => 255]);

    $sendung = BestellungModals::sendung(Bestellung::create([
        'bestellt_bei' => 'X', 'bestellt_was' => 'Y', 'bezahlt_mit' => 'Z', 'bestellt_am' => now(),
    ]));
    $nummer = collect($sendung['blocks'])->firstWhere('block_id', 'sendungsnummer');
    expect($nummer['element']['max_length'])->toBe(100);
});
