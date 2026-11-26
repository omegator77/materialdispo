<?php

use App\Models\Bestellung;
use App\Models\Setting;
use App\Models\User;
use App\Services\SlackBestellungSync;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

function bestellungFuerSync(array $attrs = []): Bestellung
{
    $user = User::factory()->create(['name' => 'Berta Besteller']);

    return Bestellung::create($attrs + [
        'bestellt_bei' => 'Thomann',
        'bestellt_was' => 'Kabel',
        'bezahlt_mit' => 'Firmenkarte',
        'bestellt_am' => '2026-11-20 14:35:00',
        'created_by' => $user->id,
    ]);
}

function bestellungActionIds(array $blocks): array
{
    return collect($blocks)->where('type', 'actions')->flatMap(fn ($b) => $b['elements'])->pluck('action_id')->all();
}

function bestellungSlackBody(string $method): array
{
    return Http::recorded(fn (Request $r) => str_contains($r->url(), $method))->map(fn ($pair) => $pair[0]->data())->values()->all();
}

beforeEach(function () {
    config(['services.slack.bot_token' => 'xoxb-test', 'services.slack.order_channel' => 'C900']);
    Http::fake(['slack.com/api/chat.postMessage' => Http::response(['ok' => true, 'channel' => 'C900', 'ts' => '111.222']), 'slack.com/*' => Http::response(['ok' => true])]);
});

test('erster Sync postet in den Bestellkanal und speichert ts, zweiter aktualisiert', function () {
    $b = bestellungFuerSync();

    app(SlackBestellungSync::class)->sync($b);

    $b->refresh();
    expect($b->slack_message_ts)->toBe('111.222')->and($b->slack_channel)->toBe('C900');
    $post = bestellungSlackBody('chat.postMessage');
    expect($post)->toHaveCount(1)
        ->and($post[0]['channel'])->toBe('C900')
        ->and(json_encode($post[0]['blocks'], JSON_UNESCAPED_UNICODE))->toContain('🛒 BESTELLUNG — Thomann')->toContain('Kabel')->toContain('Firmenkarte')->toContain('20.11.2026 14:35')->toContain('Berta Besteller')->toContain('Rechnung als Datei in den Thread');

    app(SlackBestellungSync::class)->sync($b);

    $update = bestellungSlackBody('chat.update');
    expect($update)->toHaveCount(1)->and($update[0]['ts'])->toBe('111.222')->and($update[0]['channel'])->toBe('C900');
    expect(bestellungSlackBody('chat.postMessage'))->toHaveCount(1);
});

test('Kanal aus der Einstellung hat Vorrang vor der Konfiguration', function () {
    Setting::set('slack_order_channel', 'C777');

    app(SlackBestellungSync::class)->sync(bestellungFuerSync());

    expect(bestellungSlackBody('chat.postMessage')[0]['channel'])->toBe('C777');
});

test('Knöpfe je Stufe', function () {
    $sync = app(SlackBestellungSync::class);
    $ids = function (Bestellung $b) use ($sync) {
        $sync->sync($b);
        $body = collect(bestellungSlackBody('chat.'))->last();

        return bestellungActionIds($body['blocks']);
    };

    $b = bestellungFuerSync();
    expect($ids($b))->toBe(['bestellung:sendung', 'bestellung:angekommen', 'bestellung:rechnung']);

    $b = bestellungFuerSync(['sendungsnummer' => '123', 'versanddienstleister' => 'dhl']);
    expect($ids($b))->toBe(['bestellung:sendung', 'bestellung:angekommen', 'bestellung:rechnung']);

    $u = User::factory()->create();
    $b = bestellungFuerSync();
    $b->markRechnungVorhanden($u);
    expect($ids($b->fresh()))->toBe(['bestellung:sendung', 'bestellung:angekommen', 'bestellung:abgelegt']);

    $b->markAbgelegt($u);
    expect($ids($b->fresh()))->toBe([]);
});

test('Knopf-Wert ist die Bestellungs-ID', function () {
    $b = bestellungFuerSync();
    app(SlackBestellungSync::class)->sync($b);

    $buttons = collect(bestellungSlackBody('chat.postMessage')[0]['blocks'])->where('type', 'actions')->first()['elements'];
    expect(collect($buttons)->pluck('value')->unique()->all())->toBe([(string) $b->id]);
});

test('abgelegt zeigt Abschlusszeile ohne Knöpfe', function () {
    $u = User::factory()->create(['name' => 'Anna Ablage']);
    $b = bestellungFuerSync();
    $b->markAbgelegt($u);

    app(SlackBestellungSync::class)->sync($b->fresh());

    expect(json_encode(bestellungSlackBody('chat.postMessage')[0]['blocks'], JSON_UNESCAPED_UNICODE))->toContain('✅ bezahlt & abgelegt von Anna Ablage am');
});

test('unterwegs enthält Link mit kodierter Nummer', function () {
    $b = bestellungFuerSync(['sendungsnummer' => 'AB 12/3', 'versanddienstleister' => 'dhl']);

    app(SlackBestellungSync::class)->sync($b);

    $json = json_encode(bestellungSlackBody('chat.postMessage')[0]['blocks'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    expect($json)->toContain('DHL')->toContain('piececode=AB%2012%2F3|AB 12/3>');
});

test('Pipe und Größer-Zeichen in der Sendungsnummer brechen den Link nicht', function () {
    $b = bestellungFuerSync(['sendungsnummer' => 'X|Y>Z', 'versanddienstleister' => 'ups']);

    app(SlackBestellungSync::class)->sync($b);

    $json = json_encode(bestellungSlackBody('chat.postMessage')[0]['blocks'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    expect($json)->toContain('tracknum=X%7CY%3EZ|X¦Y&gt;Z>');
});

test('Freitext wird maskiert', function () {
    $evil = '<!channel> & *fett* <@U1>';
    $b = bestellungFuerSync(['bestellt_bei' => $evil, 'bestellt_was' => $evil, 'bezahlt_mit' => $evil, 'sendungsnummer' => $evil, 'versanddienstleister' => 'sonstige']);

    app(SlackBestellungSync::class)->sync($b);

    $body = bestellungSlackBody('chat.postMessage')[0];
    $json = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    expect($json)->not->toContain('<!channel>')->not->toContain('<@U1>')->toContain('&lt;!channel&gt;');
    expect($body['text'])->not->toContain('<!channel>');
});

test('ohne Kanal oder Token kein HTTP-Aufruf', function () {
    config(['services.slack.order_channel' => null]);
    app(SlackBestellungSync::class)->sync(bestellungFuerSync());
    config(['services.slack.order_channel' => 'C900', 'services.slack.bot_token' => null]);
    app(SlackBestellungSync::class)->sync(bestellungFuerSync());

    Http::assertNothingSent();
});

test('Verbindungsfehler werfen nicht', function () {
    Http::fake(fn () => throw new ConnectionException('down'));
    $b = bestellungFuerSync();

    app(SlackBestellungSync::class)->sync($b);
    app(SlackBestellungSync::class)->threadReply($b, 'Hallo');

    expect($b->fresh()->slack_message_ts)->toBeNull();
});

test('threadReply sendet thread_ts', function () {
    $b = bestellungFuerSync(['slack_channel' => 'C900', 'slack_message_ts' => '5.5']);

    app(SlackBestellungSync::class)->threadReply($b, 'Hinweis');

    $post = bestellungSlackBody('chat.postMessage');
    expect($post)->toHaveCount(1)->and($post[0]['thread_ts'])->toBe('5.5')->and($post[0]['channel'])->toBe('C900')->and($post[0]['text'])->toBe('Hinweis');
});

test('sehr lange Texte lassen jeden Abschnitt unter 3000 Zeichen und werden trotzdem gepostet', function () {
    foreach ([str_repeat('x', 2000), str_repeat('&', 2000)] as $lang) {
        $b = bestellungFuerSync(['bestellt_was' => $lang, 'bestellt_bei' => str_repeat('<', 255), 'bezahlt_mit' => str_repeat('&', 255)]);

        app(SlackBestellungSync::class)->sync($b);

        expect($b->fresh()->slack_message_ts)->not->toBeNull()->and($b->fresh()->bestellt_was)->toHaveLength(2000);
        $body = collect(bestellungSlackBody('chat.postMessage'))->last();
        foreach ($body['blocks'] as $block) {
            if ($block['type'] === 'section') {
                expect(mb_strlen($block['text']['text']))->toBeLessThan(3000);
            }
        }
        expect(json_encode($body['blocks'], JSON_UNESCAPED_UNICODE))->toContain('vollständig in der App');
    }
});

function bestellungSyncText(Bestellung $b): string
{
    app(SlackBestellungSync::class)->sync($b);

    return json_encode(bestellungSlackBody('chat.postMessage')[0]['blocks'], JSON_UNESCAPED_UNICODE);
}

test('ohne Ankunft: Knopf Sendung angekommen in richtiger Reihenfolge, keine Paketzeile', function () {
    $b = bestellungFuerSync();
    $text = bestellungSyncText($b);

    expect(bestellungActionIds(bestellungSlackBody('chat.postMessage')[0]['blocks']))
        ->toBe(['bestellung:sendung', 'bestellung:angekommen', 'bestellung:rechnung'])
        ->and($text)->toContain('Sendung angekommen')->not->toContain('📦');
});

test('Rechnungsstufe behält Reihenfolge mit Angekommen-Knopf', function () {
    $b = bestellungFuerSync(['rechnung_vorhanden_at' => now()]);
    bestellungSyncText($b);

    expect(bestellungActionIds(bestellungSlackBody('chat.postMessage')[0]['blocks']))
        ->toBe(['bestellung:sendung', 'bestellung:angekommen', 'bestellung:abgelegt']);
});

test('mit Ankunft und Notiz: Zeile statt Knopf', function () {
    $anna = User::factory()->create(['name' => 'Anna']);
    $b = bestellungFuerSync(['angekommen_at' => '2026-11-26 14:05:00', 'angekommen_by' => $anna->id, 'angekommen_notiz' => 'Lager B']);
    $text = bestellungSyncText($b);

    expect($text)->toContain('📦 Angekommen am 26.11.2026 14:05 Uhr von Anna — Lager B')
        ->and(bestellungActionIds(bestellungSlackBody('chat.postMessage')[0]['blocks']))->not->toContain('bestellung:angekommen');
});

test('mit Ankunft ohne Notiz: keine Notizfortsetzung', function () {
    $anna = User::factory()->create(['name' => 'Anna']);
    $b = bestellungFuerSync(['angekommen_at' => '2026-11-26 14:05:00', 'angekommen_by' => $anna->id]);

    expect(bestellungSyncText($b))->toContain('📦 Angekommen am 26.11.2026 14:05 Uhr von Anna"')->not->toContain('Uhr von Anna —');
});

test('abgelegt behält die Paketzeile ohne Knopf', function () {
    $anna = User::factory()->create(['name' => 'Anna']);
    $b = bestellungFuerSync(['angekommen_at' => '2026-11-26 14:05:00', 'angekommen_by' => $anna->id, 'rechnung_vorhanden_at' => now(), 'abgelegt_at' => now()]);

    expect(bestellungSyncText($b))->toContain('📦 Angekommen am')->and(bestellungActionIds(bestellungSlackBody('chat.postMessage')[0]['blocks']))->toBe([]);
});

test('Notiz wird maskiert und gekürzt', function () {
    $anna = User::factory()->create(['name' => 'Anna']);
    $b = bestellungFuerSync(['angekommen_at' => '2026-11-26 14:05:00', 'angekommen_by' => $anna->id, 'angekommen_notiz' => '<!channel> <@U1> & *fett*']);
    $text = bestellungSyncText($b);

    expect($text)->toContain('&lt;!channel&gt;')->toContain('&lt;@U1&gt;')->toContain('&amp;')->not->toContain('<!channel>')->not->toContain('<@U1>');

    $lang = bestellungFuerSync(['angekommen_at' => '2026-11-26 14:05:00', 'angekommen_by' => $anna->id, 'angekommen_notiz' => str_repeat('&', 255)]);
    app(SlackBestellungSync::class)->sync($lang);
    foreach (bestellungSlackBody('chat.postMessage')[1]['blocks'] as $block) {
        if (isset($block['text']['text'])) {
            expect(mb_strlen($block['text']['text']))->toBeLessThan(3000);
        }
    }
});
