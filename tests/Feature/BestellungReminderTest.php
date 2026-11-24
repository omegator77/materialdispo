<?php

use App\Models\Bestellung;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

function erinnerungBestellung(array $attrs = []): Bestellung
{
    $user = User::factory()->create();

    return Bestellung::create($attrs + [
        'bestellt_bei' => 'Thomann & Co',
        'bestellt_was' => 'Kabel',
        'bezahlt_mit' => 'Firmenkarte',
        'bestellt_am' => '2026-09-01 10:00:00',
        'created_by' => $user->id,
        'slack_channel' => 'C900',
        'slack_message_ts' => '100.200',
        'rechnung_vorhanden_at' => '2026-10-01 08:00:00',
    ]);
}

function erinnerungReplies(): array
{
    return Http::recorded(fn (Request $r) => str_contains($r->url(), 'chat.postMessage'))
        ->map(fn ($pair) => $pair[0]->data())->values()->all();
}

beforeEach(function () {
    config(['services.slack.bot_token' => 'xoxb-test']);
    Http::fake([
        'slack.com/api/users.lookupByEmail*' => fn (Request $r) => str_contains($r->url(), 'bh1') ? Http::response(['ok' => true, 'user' => ['id' => 'UB1']]) : Http::response(['ok' => false, 'error' => 'users_not_found']),
        'slack.com/*' => Http::response(['ok' => true]),
    ]);
    User::factory()->create(['role' => 'user', 'ist_buchhaltung' => true, 'email' => 'bh1@example.com']);
});

test('nach 31 Tagen genau eine Erinnerung mit Erwähnung und Bestellname', function () {
    Carbon::setTestNow('2026-11-01 09:00:00');
    $b = erinnerungBestellung();

    $this->artisan('bestellungen:erinnern')->assertSuccessful();

    $replies = erinnerungReplies();
    expect($replies)->toHaveCount(1)
        ->and($replies[0]['thread_ts'])->toBe('100.200')
        ->and($replies[0]['channel'])->toBe('C900')
        ->and($replies[0]['text'])->toContain('<@UB1>')->toContain('seit 31 Tagen')->toContain('Thomann &amp; Co')
        ->and($b->fresh()->letzte_erinnerung_at->toDateTimeString())->toBe('2026-11-01 09:00:00');
});

test('unter 30 Tagen keine Erinnerung', function () {
    Carbon::setTestNow('2026-10-30 09:00:00');
    erinnerungBestellung();

    $this->artisan('bestellungen:erinnern')->assertSuccessful();

    expect(erinnerungReplies())->toBeEmpty();
});

test('zweiter Lauf am selben Tag sendet nichts, nach 8 Tagen wieder', function () {
    Carbon::setTestNow('2026-11-01 09:00:00');
    erinnerungBestellung();

    $this->artisan('bestellungen:erinnern');
    $this->artisan('bestellungen:erinnern');
    expect(erinnerungReplies())->toHaveCount(1);

    Carbon::setTestNow('2026-11-09 09:00:00');
    $this->artisan('bestellungen:erinnern');
    expect(erinnerungReplies())->toHaveCount(2);
});

test('abgelegte Bestellungen werden übersprungen', function () {
    Carbon::setTestNow('2026-11-01 09:00:00');
    erinnerungBestellung(['abgelegt_at' => '2026-10-20 10:00:00']);

    $this->artisan('bestellungen:erinnern')->assertSuccessful();

    expect(erinnerungReplies())->toBeEmpty();
});

test('Bestellungen ohne Slack-Nachricht werden übersprungen', function () {
    Carbon::setTestNow('2026-11-01 09:00:00');
    erinnerungBestellung(['slack_message_ts' => null]);

    $this->artisan('bestellungen:erinnern')->assertSuccessful();

    expect(erinnerungReplies())->toBeEmpty();
});

test('ohne erwähnbaren Buchhaltungs-Benutzer wird mit Klartext Buchhaltung erinnert', function () {
    Carbon::setTestNow('2026-11-01 09:00:00');
    User::where('email', 'bh1@example.com')->delete();
    $b = erinnerungBestellung();

    $this->artisan('bestellungen:erinnern')->assertSuccessful();

    $replies = erinnerungReplies();
    expect($replies)->toHaveCount(1)->and($replies[0]['text'])->toStartWith('Buchhaltung:')
        ->and($b->fresh()->letzte_erinnerung_at)->not->toBeNull();
});

test('Benutzer ohne Slack-Konto wird nicht erwähnt', function () {
    Carbon::setTestNow('2026-11-01 09:00:00');
    User::factory()->create(['role' => 'user', 'ist_buchhaltung' => true, 'email' => 'ohne@example.com']);
    erinnerungBestellung();

    $this->artisan('bestellungen:erinnern');

    expect(erinnerungReplies()[0]['text'])->toContain('<@UB1>')->not->toContain('ohne')
        ->and(substr_count(erinnerungReplies()[0]['text'], '<@'))->toBe(1);
});

test('Slack-Fehler bei der ersten Bestellung: zweite wird erinnert, Exit FAILURE, nächster Lauf wiederholt', function () {
    Carbon::setTestNow('2026-11-01 09:00:00');
    $n = 0;
    Http::swap(new Factory);
    Http::fake([
        'slack.com/api/chat.postMessage' => function () use (&$n) {
            return ++$n === 1 ? Http::response(['ok' => false, 'error' => 'channel_not_found']) : Http::response(['ok' => true]);
        },
        'slack.com/*' => Http::response(['ok' => false]),
    ]);
    $a = erinnerungBestellung();
    $b = erinnerungBestellung();

    $this->artisan('bestellungen:erinnern')->assertFailed();

    expect($a->fresh()->letzte_erinnerung_at)->toBeNull()->and($b->fresh()->letzte_erinnerung_at)->not->toBeNull();

    Carbon::setTestNow('2026-11-02 09:00:00');
    $this->artisan('bestellungen:erinnern')->assertSuccessful();
    expect($a->fresh()->letzte_erinnerung_at)->not->toBeNull()->and($n)->toBe(3);
});

test('Exception bei der ersten Bestellung stoppt den Lauf nicht und stellt den Zustand wieder her', function () {
    Carbon::setTestNow('2026-11-01 09:00:00');
    $n = 0;
    Http::swap(new Factory);
    Http::fake([
        'slack.com/api/chat.postMessage' => function () use (&$n) {
            if (++$n === 1) {
                throw new RuntimeException('boom');
            }

            return Http::response(['ok' => true]);
        },
        'slack.com/*' => Http::response(['ok' => false]),
    ]);
    $a = erinnerungBestellung();
    $b = erinnerungBestellung();

    $this->artisan('bestellungen:erinnern')->assertFailed();

    expect($a->fresh()->letzte_erinnerung_at)->toBeNull()->and($b->fresh()->letzte_erinnerung_at)->not->toBeNull();
});

test('zwischenzeitlich abgelegte Bestellung bekommt keine Erinnerung', function () {
    Carbon::setTestNow('2026-11-01 09:00:00');
    $a = erinnerungBestellung();
    $b = erinnerungBestellung();
    $n = 0;
    Http::swap(new Factory);
    Http::fake([
        'slack.com/api/chat.postMessage' => function () use (&$n, $b) {
            if (++$n === 1) {
                Bestellung::whereKey($b->id)->update(['abgelegt_at' => now()]);
            }

            return Http::response(['ok' => true]);
        },
        'slack.com/*' => Http::response(['ok' => false]),
    ]);

    $this->artisan('bestellungen:erinnern')->assertSuccessful();

    expect($n)->toBe(1)->and($b->fresh()->letzte_erinnerung_at)->toBeNull();
});

test('Wiederholung nach genau 7 Tagen trotz Claim um 09:00:05, nicht nach 6 Tagen 22 Stunden', function () {
    Carbon::setTestNow('2026-11-01 09:00:05');
    erinnerungBestellung();
    $this->artisan('bestellungen:erinnern');
    expect(erinnerungReplies())->toHaveCount(1);

    Carbon::setTestNow('2026-11-08 07:00:00'); // 6 Tage 22 Stunden später
    $this->artisan('bestellungen:erinnern');
    expect(erinnerungReplies())->toHaveCount(1);

    Carbon::setTestNow('2026-11-08 09:00:00');
    $this->artisan('bestellungen:erinnern');
    expect(erinnerungReplies())->toHaveCount(2);
});

test('genau 30 Tage nach Rechnung ist bereits fällig (<=)', function () {
    Carbon::setTestNow('2026-10-31 08:00:00'); // rechnung_vorhanden_at = 2026-10-01 08:00:00
    erinnerungBestellung();

    $this->artisan('bestellungen:erinnern');

    expect(erinnerungReplies())->toHaveCount(1)->and(erinnerungReplies()[0]['text'])->toContain('seit 30 Tagen');
});

test('Befehl ist im Scheduler eingetragen', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($e) => str_contains($e->command, 'bestellungen:erinnern'));

    expect($event)->not->toBeNull()->and($event->expression)->toBe('0 9 * * *')->and($event->withoutOverlapping)->toBeTrue();
});

afterEach(fn () => Carbon::setTestNow());
