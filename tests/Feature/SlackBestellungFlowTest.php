<?php

use App\Models\Bestellung;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Spatie\Activitylog\Models\Activity;

const BST_SECRET = 'bst-signing-secret';

function bstSigned(object $test, string $uri, string $body, ?string $signature = null)
{
    $timestamp = time();
    $signature ??= 'v0='.hash_hmac('sha256', "v0:{$timestamp}:{$body}", BST_SECRET);

    parse_str($body, $params);

    return $test->call('POST', $uri, $params, [], [], [
        'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
        'HTTP_X_SLACK_REQUEST_TIMESTAMP' => (string) $timestamp,
        'HTTP_X_SLACK_SIGNATURE' => $signature,
    ], $body);
}

function bstCommand(object $test, string $slackUser = 'U1', ?string $signature = null)
{
    $body = http_build_query(['command' => '/bestellung', 'user_id' => $slackUser, 'trigger_id' => 'TRIG1']);

    return bstSigned($test, '/slack/commands', $body, $signature);
}

function bstInteraction(object $test, array $payload)
{
    return bstSigned($test, '/slack/interactions', 'payload='.urlencode(json_encode($payload)));
}

function bstSubmit(object $test, string $callback, array $values, string $viewId = 'V1', string $meta = '', string $slackUser = 'U1')
{
    $state = [];
    foreach ($values as $name => $value) {
        $state[$name] = [$name => is_array($value) ? $value : ['type' => 'plain_text_input', 'value' => $value]];
    }

    return bstInteraction($test, [
        'type' => 'view_submission',
        'user' => ['id' => $slackUser],
        'view' => ['id' => $viewId, 'callback_id' => $callback, 'private_metadata' => $meta, 'state' => ['values' => $state]],
    ]);
}

function bstClick(object $test, string $action, Bestellung $b, string $slackUser = 'U1')
{
    return bstInteraction($test, [
        'type' => 'block_actions',
        'user' => ['id' => $slackUser, 'username' => 'klicker'],
        'trigger_id' => 'TRIG2',
        'response_url' => 'https://hooks.slack.test/response',
        'actions' => [['action_id' => $action, 'value' => (string) $b->id]],
    ]);
}

function bstFake(array $emails = ['U1' => 'crew@example.com'], array $extra = []): void
{
    Http::fake($extra + [
        'slack.com/api/users.info*' => fn (Request $r) => Http::response(['ok' => true, 'user' => ['profile' => ['email' => $emails[$r['user']] ?? null]]]),
        'slack.com/api/users.lookupByEmail*' => fn (Request $r) => Http::response(
            ($id = array_search($r['email'], $emails, true)) !== false
                ? ['ok' => true, 'user' => ['id' => $id]]
                : ['ok' => false, 'error' => 'users_not_found']),
        'slack.com/api/views.open' => Http::response(['ok' => true]),
        'slack.com/api/chat.postMessage' => Http::response(['ok' => true, 'channel' => 'C1', 'ts' => '111.222']),
        'slack.com/api/chat.update' => Http::response(['ok' => true]),
        'hooks.slack.test/*' => Http::response('ok'),
        '*' => Http::response(['ok' => true]),
    ]);
}

function bstOrder(array $attrs = []): Bestellung
{
    return Bestellung::create($attrs + [
        'bestellt_bei' => 'Shop', 'bestellt_was' => 'Kabel', 'bezahlt_mit' => 'Karte',
        'bestellt_am' => now(), 'slack_channel' => 'C1', 'slack_message_ts' => '100.200',
    ]);
}

function bstCalls(string $method)
{
    return Http::recorded(fn (Request $r) => str_contains($r->url(), "slack.com/api/{$method}"));
}

beforeEach(function () {
    Cache::flush();
    config([
        'services.slack.signing_secret' => BST_SECRET,
        'services.slack.bot_token' => 'xoxb-test',
        'services.slack.order_channel' => 'C1',
    ]);
});

test('Befehl ohne gültige Signatur wird abgelehnt', function () {
    bstFake();

    bstCommand($this, 'U1', 'v0=falsch')->assertStatus(401);
    expect(bstCalls('views.open'))->toHaveCount(0);
});

test('Befehl von unbekanntem Nutzer oder Betrachter bekommt flüchtige Ablehnung', function () {
    User::factory()->create(['role' => 'viewer', 'email' => 'viewer@example.com']);
    bstFake(['U1' => 'viewer@example.com']);

    bstCommand($this, 'U1')->assertOk()->assertJson(['response_type' => 'ephemeral'])
        ->assertJsonPath('text', fn ($t) => str_starts_with($t, '🚫 Keine Berechtigung'));
    bstCommand($this, 'U9')->assertOk()->assertJson(['response_type' => 'ephemeral']);

    expect(bstCalls('views.open'))->toHaveCount(0);
});

test('Befehl öffnet das Formular', function () {
    User::factory()->create(['role' => 'user', 'email' => 'crew@example.com']);
    bstFake();

    bstCommand($this)->assertOk()->assertContent('');

    $calls = bstCalls('views.open');
    expect($calls)->toHaveCount(1);
    $req = $calls->first()[0];
    expect($req['trigger_id'])->toBe('TRIG1')
        ->and($req['view']['callback_id'])->toBe('bestellung_neu')
        ->and(mb_strlen($req['view']['title']['text']))->toBeLessThanOrEqual(24);
    $ids = collect($req['view']['blocks'])->pluck('block_id')->all();
    expect($ids)->toBe(['bestellt_bei', 'bestellt_was', 'bezahlt_mit']);
    expect($req['view']['blocks'][1]['element']['multiline'])->toBeTrue();
});

test('Befehl meldet, wenn das Formular nicht geöffnet werden kann', function () {
    User::factory()->create(['role' => 'user', 'email' => 'crew@example.com']);
    bstFake(extra: ['slack.com/api/views.open' => Http::response(['ok' => false, 'error' => 'expired_trigger_id'])]);

    bstCommand($this)->assertOk()->assertJson(['response_type' => 'ephemeral', 'text' => 'Formular konnte nicht geöffnet werden.']);
});

test('Slack-Ausfall beim Öffnen des Formulars ergibt keinen 500er', function () {
    User::factory()->create(['role' => 'user', 'email' => 'crew@example.com']);
    bstFake(extra: ['slack.com/api/views.open' => fn () => throw new ConnectionException('down')]);

    bstCommand($this)->assertOk()->assertJson(['text' => 'Formular konnte nicht geöffnet werden.']);
});

test('Absenden legt die Bestellung an und postet in den Kanal', function () {
    $user = User::factory()->create(['role' => 'user', 'email' => 'crew@example.com']);
    bstFake();

    bstSubmit($this, 'bestellung_neu', [
        'bestellt_bei' => '  Thomann ', 'bestellt_was' => "2x XLR\n3x Gaffa", 'bezahlt_mit' => 'Firmenkarte',
    ], 'VIEW-A')->assertOk()->assertContent('');

    $b = Bestellung::sole();
    expect($b->bestellt_bei)->toBe('Thomann')
        ->and($b->bestellt_was)->toBe("2x XLR\n3x Gaffa")
        ->and($b->bezahlt_mit)->toBe('Firmenkarte')
        ->and($b->created_by)->toBe($user->id)
        ->and($b->slack_view_id)->toBe('VIEW-A')
        ->and($b->bestellt_am->diffInSeconds(now(), true))->toBeLessThan(10)
        ->and($b->slack_message_ts)->toBe('111.222');
    expect(bstCalls('chat.postMessage'))->toHaveCount(1);
    expect(bstCalls('chat.postMessage')->first()[0]['channel'])->toBe('C1');
});

test('Wiederholtes Absenden mit derselben view.id legt nichts doppelt an', function () {
    User::factory()->create(['role' => 'user', 'email' => 'crew@example.com']);
    bstFake();
    $values = ['bestellt_bei' => 'A', 'bestellt_was' => 'B', 'bezahlt_mit' => 'C'];

    bstSubmit($this, 'bestellung_neu', $values, 'VIEW-X')->assertOk();
    bstSubmit($this, 'bestellung_neu', $values, 'VIEW-X')->assertOk()->assertContent('');

    expect(Bestellung::count())->toBe(1);
    expect(bstCalls('chat.postMessage'))->toHaveCount(1);
});

test('Validierung liefert Feldfehler und speichert nichts', function () {
    User::factory()->create(['role' => 'user', 'email' => 'crew@example.com']);
    bstFake();

    bstSubmit($this, 'bestellung_neu', ['bestellt_bei' => '   ', 'bestellt_was' => '', 'bezahlt_mit' => ''])
        ->assertOk()->assertJsonPath('response_action', 'errors')
        ->assertJsonStructure(['errors' => ['bestellt_bei', 'bestellt_was', 'bezahlt_mit']]);

    bstSubmit($this, 'bestellung_neu', [
        'bestellt_bei' => str_repeat('a', 256), 'bestellt_was' => str_repeat('b', 2001), 'bezahlt_mit' => str_repeat('c', 256),
    ])->assertOk()->assertJsonPath('response_action', 'errors')
        ->assertJsonStructure(['errors' => ['bestellt_bei', 'bestellt_was', 'bezahlt_mit']]);

    expect(Bestellung::count())->toBe(0);
    expect(bstCalls('chat.postMessage'))->toHaveCount(0);
});

test('Absenden ohne Schreibrecht wird mit Fehler abgelehnt', function () {
    User::factory()->create(['role' => 'viewer', 'email' => 'viewer@example.com']);
    bstFake(['U1' => 'viewer@example.com']);

    bstSubmit($this, 'bestellung_neu', ['bestellt_bei' => 'A', 'bestellt_was' => 'B', 'bezahlt_mit' => 'C'])
        ->assertOk()->assertJsonPath('response_action', 'errors')
        ->assertJsonPath('errors.bestellt_bei', fn ($t) => str_starts_with($t, '🚫 Keine Berechtigung'));

    expect(Bestellung::count())->toBe(0);
});

test('Slack-Ausfall beim Posten speichert die Bestellung trotzdem', function () {
    User::factory()->create(['role' => 'user', 'email' => 'crew@example.com']);
    bstFake(extra: ['slack.com/api/chat.postMessage' => fn () => throw new ConnectionException('down')]);

    bstSubmit($this, 'bestellung_neu', ['bestellt_bei' => 'A', 'bestellt_was' => 'B', 'bezahlt_mit' => 'C'])
        ->assertOk();

    expect(Bestellung::count())->toBe(1)->and(Bestellung::sole()->slack_message_ts)->toBeNull();
});

test('Sendung eintragen öffnet das Formular mit der Bestellungs-ID', function () {
    User::factory()->create(['role' => 'user', 'email' => 'crew@example.com']);
    bstFake();
    $b = bstOrder(['versanddienstleister' => 'dpd']);

    bstClick($this, 'bestellung:sendung', $b)->assertNoContent();

    $req = bstCalls('views.open')->first()[0];
    expect($req['trigger_id'])->toBe('TRIG2')
        ->and($req['view']['callback_id'])->toBe('bestellung_sendung')
        ->and($req['view']['private_metadata'])->toBe((string) $b->id);
    expect(collect($req['view']['blocks'])->pluck('block_id')->all())->toBe(['versanddienstleister', 'sendungsnummer']);
    expect(collect($req['view']['blocks'][0]['element']['options'])->pluck('value')->all())->toBe(array_keys(Bestellung::CARRIERS));
});

test('Sendung eintragen ohne Schreibrecht öffnet nichts', function () {
    User::factory()->create(['role' => 'viewer', 'email' => 'viewer@example.com']);
    bstFake(['U1' => 'viewer@example.com']);
    $b = bstOrder();

    bstClick($this, 'bestellung:sendung', $b)->assertNoContent();

    expect(bstCalls('views.open'))->toHaveCount(0);
    expect(Http::recorded(fn (Request $r) => $r->url() === 'https://hooks.slack.test/response' && ($r['response_type'] ?? null) === 'ephemeral'))->toHaveCount(1);
});

test('Sendungsformular setzt Dienst und Nummer und aktualisiert die Nachricht', function () {
    $user = User::factory()->create(['role' => 'user', 'email' => 'crew@example.com']);
    bstFake();
    $b = bstOrder();

    bstSubmit($this, 'bestellung_sendung', [
        'versanddienstleister' => ['type' => 'static_select', 'selected_option' => ['value' => 'dhl']],
        'sendungsnummer' => '  JJD123 ',
    ], 'V2', (string) $b->id)->assertOk()->assertContent('');

    $b->refresh();
    expect($b->versanddienstleister)->toBe('dhl')->and($b->sendungsnummer)->toBe('JJD123')
        ->and($b->stufe())->toBe(Bestellung::STUFE_UNTERWEGS);
    expect(bstCalls('chat.update'))->toHaveCount(1);
    $log = Activity::where('event', 'sendung_eingetragen')->sole();
    expect($log->causer_id)->toBe($user->id)->and($log->subject_id)->toBe($b->id);
});

test('Sendungsformular validiert Dienst und Nummer', function () {
    User::factory()->create(['role' => 'user', 'email' => 'crew@example.com']);
    bstFake();
    $b = bstOrder();

    bstSubmit($this, 'bestellung_sendung', [
        'versanddienstleister' => ['type' => 'static_select', 'selected_option' => ['value' => 'foo']],
        'sendungsnummer' => '   ',
    ], 'V3', (string) $b->id)->assertOk()->assertJsonPath('response_action', 'errors')
        ->assertJsonStructure(['errors' => ['versanddienstleister', 'sendungsnummer']]);

    bstSubmit($this, 'bestellung_sendung', [
        'versanddienstleister' => ['type' => 'static_select', 'selected_option' => ['value' => 'dhl']],
        'sendungsnummer' => str_repeat('1', 101),
    ], 'V3', (string) $b->id)->assertJsonStructure(['errors' => ['sendungsnummer']]);

    expect($b->fresh()->sendungsnummer)->toBeNull();
});

test('Rechnung liegt vor erwähnt die Buchhaltung mit Slack-Konto', function () {
    $user = User::factory()->create(['role' => 'user', 'email' => 'crew@example.com']);
    User::factory()->create(['role' => 'user', 'ist_buchhaltung' => true, 'email' => 'bh1@example.com']);
    User::factory()->create(['role' => 'user', 'ist_buchhaltung' => true, 'email' => 'bh2@example.com']);
    User::factory()->create(['role' => 'user', 'ist_buchhaltung' => true, 'email' => 'ohne@example.com']);
    bstFake(['U1' => 'crew@example.com', 'UB1' => 'bh1@example.com', 'UB2' => 'bh2@example.com']);
    $b = bstOrder();

    bstClick($this, 'bestellung:rechnung', $b)->assertNoContent();

    expect($b->fresh()->rechnung_vorhanden_at)->not->toBeNull()
        ->and($b->fresh()->rechnung_vorhanden_by)->toBe($user->id);
    $reply = bstCalls('chat.postMessage')->first(fn ($c) => isset($c[0]['thread_ts']))[0];
    expect($reply['text'])->toContain('<@UB1>')->toContain('<@UB2>')->not->toContain('ohne')
        ->and($reply['thread_ts'])->toBe('100.200');
    expect(bstCalls('chat.update'))->toHaveCount(1);
    expect(Activity::where('event', 'rechnung_vorhanden')->sole()->causer_id)->toBe($user->id);
});

test('Rechnung liegt vor ohne Slack-Konto der Buchhaltung nutzt Klartext', function () {
    User::factory()->create(['role' => 'admin', 'email' => 'crew@example.com']);
    User::factory()->create(['role' => 'user', 'ist_buchhaltung' => true, 'email' => 'bh@example.com']);
    bstFake();
    $b = bstOrder();

    bstClick($this, 'bestellung:rechnung', $b)->assertNoContent();

    $reply = bstCalls('chat.postMessage')->first(fn ($c) => isset($c[0]['thread_ts']))[0];
    expect($reply['text'])->toStartWith('Buchhaltung:')->not->toContain('<@');
});

test('Rechnung liegt vor ohne Schreibrecht ändert nichts', function () {
    User::factory()->create(['role' => 'viewer', 'email' => 'viewer@example.com']);
    bstFake(['U1' => 'viewer@example.com']);
    $b = bstOrder();

    bstClick($this, 'bestellung:rechnung', $b)->assertNoContent();

    expect($b->fresh()->rechnung_vorhanden_at)->toBeNull();
    expect(bstCalls('chat.postMessage'))->toHaveCount(0);
});

test('Bezahlt und abgelegt nur für Buchhaltung', function () {
    $bh = User::factory()->create(['role' => 'user', 'ist_buchhaltung' => true, 'email' => 'bh@example.com']);
    User::factory()->create(['role' => 'admin', 'email' => 'admin@example.com']);
    User::factory()->create(['role' => 'user', 'email' => 'crew@example.com']);
    bstFake(['UA' => 'admin@example.com', 'UC' => 'crew@example.com', 'UB' => 'bh@example.com']);
    $b = bstOrder(['rechnung_vorhanden_at' => now()]);

    foreach (['UA', 'UC'] as $denied) {
        bstClick($this, 'bestellung:abgelegt', $b, $denied)->assertNoContent();
        expect($b->fresh()->abgelegt_at)->toBeNull();
    }
    expect(Http::recorded(fn (Request $r) => $r->url() === 'https://hooks.slack.test/response' && ($r['response_type'] ?? null) === 'ephemeral'))->toHaveCount(2);
    expect(bstCalls('chat.update'))->toHaveCount(0);

    bstClick($this, 'bestellung:abgelegt', $b, 'UB')->assertNoContent();

    $b->refresh();
    expect($b->abgelegt_at)->not->toBeNull()->and($b->abgelegt_by)->toBe($bh->id);
    $update = bstCalls('chat.update');
    expect($update)->toHaveCount(1);
    expect(collect($update->first()[0]['blocks'])->pluck('type')->all())->not->toContain('actions');
    expect(Activity::where('event', 'abgelegt')->sole()->causer_id)->toBe($bh->id);
});

test('Unbekannte Bestellung bei Knopfklick ergibt keinen Fehler', function () {
    User::factory()->create(['role' => 'user', 'email' => 'crew@example.com']);
    bstFake();
    $b = bstOrder();
    $id = $b->id;
    $b->delete();

    bstInteraction($this, [
        'type' => 'block_actions', 'user' => ['id' => 'U1'], 'trigger_id' => 'T',
        'response_url' => 'https://hooks.slack.test/response',
        'actions' => [['action_id' => 'bestellung:rechnung', 'value' => (string) $id]],
    ])->assertNoContent();
});

test('Activity-Log hält Verursacher beim Anlegen fest', function () {
    $user = User::factory()->create(['role' => 'user', 'email' => 'crew@example.com']);
    bstFake();

    bstSubmit($this, 'bestellung_neu', ['bestellt_bei' => 'A', 'bestellt_was' => 'B', 'bezahlt_mit' => 'C'])->assertOk();

    $log = Activity::where('log_name', 'bestellung')->where('event', 'created')->sole();
    expect($log->causer_id)->toBe($user->id);
});
