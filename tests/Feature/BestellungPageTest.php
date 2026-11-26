<?php

use App\Models\Bestellung;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Spatie\Activitylog\Models\Activity;

function bpBestellung(array $attrs = []): Bestellung
{
    return Bestellung::create(array_merge([
        'bestellt_bei' => 'Thomann',
        'bestellt_was' => 'XLR-Kabel',
        'bezahlt_mit' => 'Kreditkarte',
        'bestellt_am' => '2026-11-20 14:35:00',
    ], $attrs));
}

function bpBuchhaltung(): User
{
    return User::factory()->create(['role' => 'user', 'ist_buchhaltung' => true]);
}

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->viewer = User::factory()->create(['role' => 'viewer']);
});

test('Liste zeigt standardmäßig nur offene Bestellungen', function () {
    bpBestellung(['bestellt_bei' => 'OffenShop']);
    bpBestellung(['bestellt_bei' => 'FertigShop', 'abgelegt_at' => now()]);

    $this->actingAs($this->admin)->get(route('bestellungen.index'))
        ->assertOk()->assertSee('OffenShop')->assertDontSee('FertigShop');
});

test('Filter abgelegt und alle', function () {
    bpBestellung(['bestellt_bei' => 'OffenShop']);
    bpBestellung(['bestellt_bei' => 'FertigShop', 'abgelegt_at' => now()]);

    $this->actingAs($this->admin)->get(route('bestellungen.index', ['status' => 'abgelegt']))
        ->assertOk()->assertSee('FertigShop')->assertDontSee('OffenShop');
    $this->actingAs($this->admin)->get(route('bestellungen.index', ['status' => 'alle']))
        ->assertOk()->assertSee('FertigShop')->assertSee('OffenShop');
});

test('Suche nach Sendungsnummer, Besteller und Text', function () {
    $anna = User::factory()->create(['name' => 'Anna Zimmermann']);
    bpBestellung(['bestellt_bei' => 'Alpha', 'sendungsnummer' => 'JJD0001234', 'versanddienstleister' => 'dhl']);
    bpBestellung(['bestellt_bei' => 'Beta', 'created_by' => $anna->id]);
    bpBestellung(['bestellt_bei' => 'Gamma', 'bestellt_was' => 'Stativ']);

    $this->actingAs($this->admin)->get(route('bestellungen.index', ['q' => 'JJD0001']))
        ->assertOk()->assertSee('Alpha')->assertDontSee('Beta')->assertDontSee('Gamma');
    $this->actingAs($this->admin)->get(route('bestellungen.index', ['q' => 'Zimmermann']))
        ->assertOk()->assertSee('Beta')->assertDontSee('Alpha')->assertDontSee('Gamma');
    $this->actingAs($this->admin)->get(route('bestellungen.index', ['q' => 'Stativ']))
        ->assertOk()->assertSee('Gamma')->assertDontSee('Alpha');
});

test('Liste zeigt Sendungslink und Stufe', function () {
    $b = bpBestellung(['versanddienstleister' => 'dhl', 'sendungsnummer' => 'ABC123']);

    $this->actingAs($this->admin)->get(route('bestellungen.index'))
        ->assertOk()->assertSee($b->trackingUrl(), false)->assertSee('Unterwegs');
});

test('Betrachter sieht Liste und Detail ohne Knöpfe und bekommt 403 auf Schreibrouten', function () {
    $b = bpBestellung();

    $this->actingAs($this->viewer)->get(route('bestellungen.index'))->assertOk();
    $this->actingAs($this->viewer)->get(route('bestellungen.show', $b))->assertOk()
        ->assertDontSee('Rechnung liegt vor</button>', false)
        ->assertDontSee('Bearbeiten')
        ->assertDontSee('In Slack neu senden');

    $this->actingAs($this->viewer)->get(route('bestellungen.edit', $b))->assertForbidden();
    $this->actingAs($this->viewer)->put(route('bestellungen.update', $b), [])->assertForbidden();
    $this->actingAs($this->viewer)->post(route('bestellungen.rechnung', $b))->assertForbidden();
    $this->actingAs($this->viewer)->post(route('bestellungen.abgelegt', $b))->assertForbidden();
    $this->actingAs($this->viewer)->post(route('bestellungen.zuruecknehmen', $b))->assertForbidden();
    $this->actingAs($this->viewer)->post(route('bestellungen.resync', $b))->assertForbidden();
});

test('Gäste werden zum Login geschickt', function () {
    $this->get(route('bestellungen.index'))->assertRedirect(route('login'));
});

test('Detailseite zeigt Stufenverlauf', function () {
    $user = User::factory()->create(['name' => 'Rita Rechnung']);
    $b = bpBestellung(['sendungsnummer' => '99', 'versanddienstleister' => 'ups']);
    $b->markRechnungVorhanden($user);

    $this->actingAs($this->admin)->get(route('bestellungen.show', $b))
        ->assertOk()->assertSee('Rita Rechnung')->assertSee('XLR-Kabel')->assertSee('Kreditkarte');
});

test('Bearbeiten speichert, protokolliert und aktualisiert Slack', function () {
    config(['services.slack.bot_token' => 'xoxb-test', 'services.slack.order_channel' => 'C123']);
    Http::fake(['slack.com/api/*' => Http::response(['ok' => true, 'ts' => '1.1', 'channel' => 'C123'])]);
    $b = bpBestellung(['slack_channel' => 'C123', 'slack_message_ts' => '1700.1']);

    $this->actingAs($this->admin)->put(route('bestellungen.update', $b), [
        'bestellt_bei' => 'Neu GmbH',
        'bestellt_was' => 'Stativ',
        'bezahlt_mit' => 'Rechnung',
        'versanddienstleister' => 'dpd',
        'sendungsnummer' => 'DPD-77',
    ])->assertRedirect(route('bestellungen.show', $b));

    $b->refresh();
    expect($b->bestellt_bei)->toBe('Neu GmbH')
        ->and($b->versanddienstleister)->toBe('dpd')
        ->and($b->sendungsnummer)->toBe('DPD-77');
    expect(Activity::where('log_name', 'bestellung')->where('causer_id', $this->admin->id)->exists())->toBeTrue();
    Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'chat.update') && $r['ts'] === '1700.1');
});

test('Bearbeiten leert Dienstleister und Sendungsnummer', function () {
    $b = bpBestellung(['versanddienstleister' => 'dhl', 'sendungsnummer' => '1']);

    $this->actingAs($this->admin)->put(route('bestellungen.update', $b), [
        'bestellt_bei' => 'Thomann', 'bestellt_was' => 'Kabel', 'bezahlt_mit' => 'Bar',
        'versanddienstleister' => '', 'sendungsnummer' => '',
    ])->assertRedirect();

    expect($b->fresh()->sendungsnummer)->toBeNull()->and($b->fresh()->versanddienstleister)->toBeNull();
});

test('Validierung beim Bearbeiten', function () {
    $b = bpBestellung();

    $this->actingAs($this->admin)->put(route('bestellungen.update', $b), [
        'bestellt_bei' => str_repeat('a', 256),
        'bestellt_was' => str_repeat('b', 2001),
        'bezahlt_mit' => '',
        'versanddienstleister' => 'fedex',
        'sendungsnummer' => str_repeat('1', 101),
    ])->assertSessionHasErrors(['bestellt_bei', 'bestellt_was', 'bezahlt_mit', 'versanddienstleister', 'sendungsnummer']);

    $this->actingAs($this->admin)->put(route('bestellungen.update', $b), [])
        ->assertSessionHasErrors(['bestellt_bei', 'bestellt_was', 'bezahlt_mit']);
});

test('Rechnung liegt vor setzt die Stufe und synchronisiert Slack', function () {
    config(['services.slack.bot_token' => 'xoxb-test']);
    Http::fake(['slack.com/api/*' => Http::response(['ok' => true])]);
    $b = bpBestellung(['slack_channel' => 'C1', 'slack_message_ts' => '5.5']);

    $this->actingAs($this->admin)->post(route('bestellungen.rechnung', $b))->assertRedirect(route('bestellungen.show', $b));

    expect($b->fresh()->stufe())->toBe('rechnung')->and($b->fresh()->rechnung_vorhanden_by)->toBe($this->admin->id);
    Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'chat.update'));
});

test('Bezahlt und abgelegt nur mit Buchhaltung', function () {
    $b = bpBestellung(['rechnung_vorhanden_at' => now()]);

    $this->actingAs($this->admin)->post(route('bestellungen.abgelegt', $b))->assertForbidden();
    $normal = User::factory()->create(['role' => 'user']);
    $this->actingAs($normal)->post(route('bestellungen.abgelegt', $b))->assertForbidden();
    expect($b->fresh()->abgelegt_at)->toBeNull();

    $buch = bpBuchhaltung();
    $this->actingAs($buch)->post(route('bestellungen.abgelegt', $b))->assertRedirect(route('bestellungen.show', $b));
    expect($b->fresh()->stufe())->toBe('abgelegt')->and($b->fresh()->abgelegt_by)->toBe($buch->id);
});

test('Zurücknehmen: abgelegt nur Buchhaltung, Rechnung für Benutzer und Admin', function () {
    $fertig = bpBestellung(['rechnung_vorhanden_at' => now(), 'abgelegt_at' => now()]);

    $this->actingAs($this->admin)->post(route('bestellungen.zuruecknehmen', $fertig))->assertForbidden();
    expect($fertig->fresh()->abgelegt_at)->not->toBeNull();

    $this->actingAs(bpBuchhaltung())->post(route('bestellungen.zuruecknehmen', $fertig))->assertRedirect();
    expect($fertig->fresh()->stufe())->toBe('rechnung');

    $this->actingAs($this->admin)->post(route('bestellungen.zuruecknehmen', $fertig))->assertRedirect();
    expect($fertig->fresh()->rechnung_vorhanden_at)->toBeNull();
});

test('Detailseite zeigt Knöpfe nach Berechtigung', function () {
    $b = bpBestellung(['rechnung_vorhanden_at' => now()]);

    $this->actingAs($this->admin)->get(route('bestellungen.show', $b))
        ->assertOk()->assertSee('Stufe zurücknehmen')->assertSee('In Slack neu senden')->assertDontSee('Bezahlt &amp; abgelegt</button>', false);
    $this->actingAs(bpBuchhaltung())->get(route('bestellungen.show', $b))
        ->assertOk()->assertSee('Bezahlt &amp; abgelegt</button>', false);
});

test('In Slack neu senden postet ohne Nachricht und aktualisiert sonst', function () {
    config(['services.slack.bot_token' => 'xoxb-test', 'services.slack.order_channel' => 'C9']);
    Http::fake(['slack.com/api/*' => Http::response(['ok' => true, 'ts' => '42.1', 'channel' => 'C9'])]);
    $b = bpBestellung();

    $this->actingAs($this->admin)->post(route('bestellungen.resync', $b))->assertRedirect(route('bestellungen.show', $b));
    Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'chat.postMessage'));
    expect($b->fresh()->slack_message_ts)->toBe('42.1');

    $this->actingAs($this->admin)->post(route('bestellungen.resync', $b))->assertRedirect();
    Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'chat.update') && $r['ts'] === '42.1');
});

test('In Slack neu senden ohne Kanal meldet einen Fehler', function () {
    config(['services.slack.bot_token' => 'xoxb-test', 'services.slack.order_channel' => null]);
    Http::fake();
    $b = bpBestellung();

    $this->actingAs($this->admin)->post(route('bestellungen.resync', $b))
        ->assertRedirect(route('bestellungen.show', $b))->assertSessionHas('error');
    Http::assertNothingSent();
});

test('Menü Verwaltung enthält Gruppe Einkauf mit Bestellungen (Desktop und Mobil)', function () {
    $html = $this->actingAs($this->admin)->get(route('dashboard'))->assertOk()->getContent();

    expect(substr_count($html, 'href="'.route('bestellungen.index').'"'))->toBeGreaterThanOrEqual(2)
        ->and($html)->toContain('Einkauf')->toContain('Bestellungen');
});

test('Einkauf-Seiten markieren Verwaltung als aktiv', function () {
    $html = $this->actingAs($this->admin)->get(route('bestellungen.index'))->assertOk()->getContent();

    expect($html)->toMatch('/border-indigo-400 text-gray-900[^>]*>\s*Verwaltung/');
});

test('Befehlspalette enthält Bestellungen', function () {
    $html = $this->actingAs($this->viewer)->get(route('dashboard'))->assertOk()->getContent();

    $json = str_replace('\\', '', str_replace('\\u0022', '"', $html));

    expect($json)->toContain('"title":"Bestellungen","url":"'.route('bestellungen.index').'"');
});

test('Bearbeiten ändert bestellt_am nicht', function () {
    $b = bpBestellung(['bestellt_am' => '2026-11-20 14:35:42']);

    $this->actingAs($this->admin)->put(route('bestellungen.update', $b), [
        'bestellt_bei' => 'Thomann', 'bestellt_was' => 'Anderes', 'bezahlt_mit' => 'Bar',
        'bestellt_am' => '2030-01-01T00:00',
    ])->assertRedirect();

    expect($b->fresh()->bestellt_am->format('Y-m-d H:i:s'))->toBe('2026-11-20 14:35:42')
        ->and($b->fresh()->bestellt_was)->toBe('Anderes');
    $changed = Activity::where('log_name', 'bestellung')->where('event', 'updated')->get()->flatMap(fn ($a) => array_keys($a->properties['attributes'] ?? []));
    expect($changed->contains('bestellt_am'))->toBeFalse();
    $this->actingAs($this->admin)->get(route('bestellungen.edit', $b))->assertDontSee('name="bestellt_am"', false);
});

test('App-Rechnung pingt Buchhaltung im Thread, nur bei Stufenwechsel', function () {
    config(['services.slack.bot_token' => 'xoxb-test']);
    Http::fake(['slack.com/api/*' => Http::response(['ok' => true])]);
    $b = bpBestellung(['slack_channel' => 'C1', 'slack_message_ts' => '5.5']);

    $this->actingAs($this->admin)->post(route('bestellungen.rechnung', $b))->assertRedirect();

    Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'chat.postMessage') && $r['thread_ts'] === '5.5' && str_contains($r['text'], 'Rechnung liegt vor'));
});

test('App-Rechnung bei bereits gesetzter Stufe pingt nicht und synchronisiert nicht', function () {
    config(['services.slack.bot_token' => 'xoxb-test']);
    Http::fake(['slack.com/api/*' => Http::response(['ok' => true])]);
    $b = bpBestellung(['slack_channel' => 'C1', 'slack_message_ts' => '5.5', 'rechnung_vorhanden_at' => now()]);

    $this->actingAs($this->admin)->post(route('bestellungen.rechnung', $b))
        ->assertRedirect(route('bestellungen.show', $b))->assertSessionHas('error', 'Stufe bereits gesetzt.')->assertSessionMissing('success');

    Http::assertNothingSent();
});

test('Abgelegt bei bereits gesetzter Stufe meldet und ändert nichts', function () {
    Http::fake();
    $b = bpBestellung(['abgelegt_at' => now()]);
    $buch = bpBuchhaltung();
    $before = Activity::count();

    $this->actingAs($buch)->post(route('bestellungen.abgelegt', $b))
        ->assertSessionHas('error', 'Stufe bereits gesetzt.')->assertSessionMissing('success');

    expect(Activity::count())->toBe($before);
    Http::assertNothingSent();
});

test('Zurücknehmen ohne Stufe meldet und ändert nichts', function () {
    Http::fake();
    $b = bpBestellung();
    $before = Activity::count();

    $this->actingAs($this->admin)->post(route('bestellungen.zuruecknehmen', $b))
        ->assertSessionHas('error', 'Keine Stufe zum Zurücknehmen.')->assertSessionMissing('success');

    expect(Activity::count())->toBe($before);
    Http::assertNothingSent();
});

function bpSlackFake(): void
{
    config(['services.slack.bot_token' => 'xoxb-test', 'services.slack.order_channel' => 'C123']);
    Http::fake(['slack.com/api/*' => Http::response(['ok' => true, 'ts' => '1.1', 'channel' => 'C123'])]);
}

test('Angekommen setzt Ankunft mit Notiz und aktualisiert Slack', function () {
    bpSlackFake();
    $b = bpBestellung(['slack_channel' => 'C123', 'slack_message_ts' => '1700.1']);

    $this->actingAs($this->admin)->post(route('bestellungen.angekommen', $b), ['notiz' => "  Regal 3\nOben  "])
        ->assertRedirect(route('bestellungen.show', $b))->assertSessionHas('success');

    $b->refresh();
    expect($b->istAngekommen())->toBeTrue()
        ->and($b->angekommen_by)->toBe($this->admin->id)
        ->and($b->angekommen_notiz)->toBe('Regal 3 Oben');
    Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'chat.update') && $r['ts'] === '1700.1');
});

test('Betrachter bekommt 403 auf Angekommen-Routen', function () {
    $b = bpBestellung();
    $this->actingAs($this->viewer)->post(route('bestellungen.angekommen', $b))->assertForbidden();
    $this->actingAs($this->viewer)->post(route('bestellungen.angekommen.zuruecknehmen', $b))->assertForbidden();
});

test('Angekommen bei bereits angekommener Sendung meldet und ändert nichts', function () {
    Http::fake();
    $b = bpBestellung(['angekommen_at' => now()->subDay(), 'angekommen_notiz' => 'alt']);
    $at = $b->fresh()->angekommen_at;
    $before = Activity::count();

    $this->actingAs($this->admin)->post(route('bestellungen.angekommen', $b), ['notiz' => 'neu'])
        ->assertSessionHas('error', 'Sendung ist bereits als angekommen eingetragen.')->assertSessionMissing('success');

    expect($b->fresh()->angekommen_notiz)->toBe('alt')->and($b->fresh()->angekommen_at->equalTo($at))->toBeTrue()
        ->and(Activity::count())->toBe($before);
    Http::assertNothingSent();
});

test('Ankunft zurücknehmen leert die Ankunft und synchronisiert', function () {
    bpSlackFake();
    $b = bpBestellung(['slack_channel' => 'C123', 'slack_message_ts' => '1700.1',
        'angekommen_at' => now(), 'angekommen_by' => $this->admin->id, 'angekommen_notiz' => 'x']);

    $this->actingAs($this->admin)->post(route('bestellungen.angekommen.zuruecknehmen', $b))
        ->assertRedirect(route('bestellungen.show', $b))->assertSessionHas('success');

    $b->refresh();
    expect($b->angekommen_at)->toBeNull()->and($b->angekommen_by)->toBeNull()->and($b->angekommen_notiz)->toBeNull();
    Http::assertSent(fn (Request $r) => str_ends_with($r->url(), 'chat.update'));
});

test('Ankunft zurücknehmen ohne Ankunft meldet und ändert nichts', function () {
    Http::fake();
    $b = bpBestellung();

    $this->actingAs($this->admin)->post(route('bestellungen.angekommen.zuruecknehmen', $b))
        ->assertSessionHas('error', 'Keine Ankunft zum Zurücknehmen.')->assertSessionMissing('success');

    Http::assertNothingSent();
});

test('Zu lange Ankunftsnotiz wird abgelehnt', function () {
    Http::fake();
    $b = bpBestellung();

    $this->actingAs($this->admin)->post(route('bestellungen.angekommen', $b), ['notiz' => str_repeat('a', 256)])
        ->assertSessionHasErrors('notiz');

    expect($b->fresh()->istAngekommen())->toBeFalse();
});

test('Detailseite zeigt Ankunft-Knopf bzw. Ankunftsdaten', function () {
    $b = bpBestellung();
    $this->actingAs($this->admin)->get(route('bestellungen.show', $b))
        ->assertOk()->assertSee('Sendung angekommen')->assertSee('name="notiz"', false)->assertDontSee('Ankunft zurücknehmen');

    $b->update(['angekommen_at' => '2026-11-25 09:30:00', 'angekommen_by' => $this->admin->id, 'angekommen_notiz' => 'Regal 3']);
    $this->actingAs($this->admin)->get(route('bestellungen.show', $b))
        ->assertOk()->assertSee('25.11.2026 09:30')->assertSee($this->admin->name)->assertSee('Regal 3')
        ->assertSee('Ankunft zurücknehmen')->assertDontSee('name="notiz"', false);

    $this->actingAs($this->viewer)->get(route('bestellungen.show', $b))
        ->assertOk()->assertSee('Regal 3')->assertDontSee('Ankunft zurücknehmen');
    $b->update(['angekommen_at' => null]);
    $this->actingAs($this->viewer)->get(route('bestellungen.show', $b))
        ->assertOk()->assertDontSee('Sendung angekommen');
});

test('Liste kennzeichnet angekommene Bestellungen', function () {
    bpBestellung(['bestellt_bei' => 'Da', 'angekommen_at' => now()]);
    $this->actingAs($this->admin)->get(route('bestellungen.index'))->assertOk()->assertSee('angekommen');

    Bestellung::query()->update(['angekommen_at' => null]);
    $this->actingAs($this->admin)->get(route('bestellungen.index'))->assertOk()->assertDontSee('angekommen');
});

test('Bearbeiten: Notizfeld nur bei Ankunft, Update ändert Ankunft nie', function () {
    Http::fake();
    $b = bpBestellung();
    $this->actingAs($this->admin)->get(route('bestellungen.edit', $b))->assertOk()->assertDontSee('angekommen_notiz');

    $payload = ['bestellt_bei' => 'Thomann', 'bestellt_was' => 'XLR-Kabel', 'bezahlt_mit' => 'Kreditkarte',
        'angekommen_notiz' => 'schleich', 'angekommen_at' => '2020-01-01 00:00:00', 'angekommen_by' => $this->admin->id];
    $this->actingAs($this->admin)->put(route('bestellungen.update', $b), $payload)->assertRedirect();
    $b->refresh();
    expect($b->angekommen_at)->toBeNull()->and($b->angekommen_by)->toBeNull()->and($b->angekommen_notiz)->toBeNull();

    $b->update(['angekommen_at' => '2026-11-25 09:30:00', 'angekommen_by' => $this->admin->id]);
    $this->actingAs($this->admin)->get(route('bestellungen.edit', $b))->assertOk()->assertSee('angekommen_notiz');
    $this->actingAs($this->admin)->put(route('bestellungen.update', $b), array_merge($payload, ['angekommen_notiz' => "  Lager\nA  "]))->assertRedirect();
    $b->refresh();
    expect($b->angekommen_notiz)->toBe('Lager A')
        ->and($b->angekommen_at->format('Y-m-d H:i'))->toBe('2026-11-25 09:30')
        ->and($b->angekommen_by)->toBe($this->admin->id);

    $this->actingAs($this->admin)->put(route('bestellungen.update', $b), array_merge($payload, ['angekommen_notiz' => str_repeat('a', 256)]))
        ->assertSessionHasErrors('angekommen_notiz');
});
