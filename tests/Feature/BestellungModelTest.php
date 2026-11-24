<?php

use App\Models\Bestellung;
use App\Models\User;
use Illuminate\Database\QueryException;
use Spatie\Activitylog\Models\Activity;

function neueBestellung(array $attrs = []): Bestellung
{
    return Bestellung::create(array_merge([
        'bestellt_bei' => 'Thomann',
        'bestellt_was' => 'Kabel',
        'bezahlt_mit' => 'Kreditkarte',
        'bestellt_am' => '2026-11-20',
    ], $attrs));
}

test('stufe liefert die höchste erreichte Stufe', function () {
    $b = neueBestellung();
    expect($b->stufe())->toBe(Bestellung::STUFE_BESTELLT);

    $b->sendungsnummer = '123';
    expect($b->stufe())->toBe(Bestellung::STUFE_UNTERWEGS);

    $b->rechnung_vorhanden_at = now();
    expect($b->stufe())->toBe(Bestellung::STUFE_RECHNUNG);

    $b->abgelegt_at = now();
    expect($b->stufe())->toBe(Bestellung::STUFE_ABGELEGT);

    $b->sendungsnummer = null;
    expect($b->stufe())->toBe(Bestellung::STUFE_ABGELEGT);
});

test('Sendung nach abgelegt ändert die Stufe nicht', function () {
    $b = neueBestellung(['abgelegt_at' => now()]);
    $b->sendungsnummer = '999';
    expect($b->stufe())->toBe('abgelegt');
});

test('stufeLabel liefert die exakten Texte', function () {
    $b = neueBestellung();
    expect($b->stufeLabel())->toBe('Bestellt');
    $b->sendungsnummer = '1';
    expect($b->stufeLabel())->toBe('Unterwegs');
    $b->rechnung_vorhanden_at = now();
    expect($b->stufeLabel())->toBe('Rechnung liegt vor');
    $b->abgelegt_at = now();
    expect($b->stufeLabel())->toBe('Bezahlt & abgelegt');
});

test('trackingUrl enthält die kodierte Nummer', function (string $carrier) {
    $b = neueBestellung(['versanddienstleister' => $carrier, 'sendungsnummer' => '12 34/ä']);
    $url = $b->trackingUrl();

    expect($url)->toStartWith('https://')
        ->and($url)->toContain(rawurlencode('12 34/ä'));
})->with(['dhl', 'dpd', 'ups', 'gls', 'hermes']);

test('trackingUrl ist null für sonstige, ohne Nummer und ohne Dienst', function () {
    expect(neueBestellung(['versanddienstleister' => 'sonstige', 'sendungsnummer' => '1'])->trackingUrl())->toBeNull();
    expect(neueBestellung(['versanddienstleister' => 'dhl'])->trackingUrl())->toBeNull();
    expect(neueBestellung(['sendungsnummer' => '1'])->trackingUrl())->toBeNull();
});

test('Scopes offen und abgelegt', function () {
    $offen = neueBestellung();
    $fertig = neueBestellung(['abgelegt_at' => now()]);

    expect(Bestellung::offen()->pluck('id')->all())->toBe([$offen->id])
        ->and(Bestellung::abgelegt()->pluck('id')->all())->toBe([$fertig->id]);
});

test('slack_view_id ist eindeutig', function () {
    neueBestellung(['slack_view_id' => 'V123']);
    neueBestellung(['slack_view_id' => 'V123']);
})->throws(QueryException::class);

test('Löschen des Benutzers setzt created_by auf null', function () {
    $user = User::factory()->create();
    $b = neueBestellung(['created_by' => $user->id]);

    $user->delete();

    expect($b->fresh()->created_by)->toBeNull();
});

test('markRechnungVorhanden setzt Zeit und Nutzer nur einmal', function () {
    $first = User::factory()->create();
    $second = User::factory()->create();
    $b = neueBestellung();

    $b->markRechnungVorhanden($first);
    $b->markRechnungVorhanden($second);

    $b->refresh();
    expect($b->rechnung_vorhanden_by)->toBe($first->id)
        ->and($b->rechnung_vorhanden_at)->not->toBeNull()
        ->and($b->stufe())->toBe('rechnung')
        ->and($b->rechnungVon->is($first))->toBeTrue();
    expect(Activity::where('log_name', 'bestellung')->where('causer_id', $first->id)->count())->toBeGreaterThan(0);
});

test('markAbgelegt setzt Zeit und Nutzer', function () {
    $user = User::factory()->create();
    $b = neueBestellung();

    $b->markAbgelegt($user);

    $b->refresh();
    expect($b->abgelegt_by)->toBe($user->id)
        ->and($b->stufe())->toBe('abgelegt')
        ->and($b->abgelegtVon->is($user))->toBeTrue();
});

test('revertStage nimmt die höchste Stufe zurück', function () {
    $user = User::factory()->create();
    $b = neueBestellung(['sendungsnummer' => '1']);
    $b->markRechnungVorhanden($user);
    $b->markAbgelegt($user);

    $b->revertStage($user);
    $b->refresh();
    expect($b->abgelegt_at)->toBeNull()->and($b->abgelegt_by)->toBeNull()
        ->and($b->stufe())->toBe('rechnung');

    $b->revertStage($user);
    $b->refresh();
    expect($b->rechnung_vorhanden_at)->toBeNull()->and($b->rechnung_vorhanden_by)->toBeNull()
        ->and($b->stufe())->toBe('unterwegs');

    $b->revertStage($user);
    $b->refresh();
    expect($b->sendungsnummer)->toBe('1')->and($b->stufe())->toBe('unterwegs');
});

test('markAbgelegt überschreibt eine vorhandene Ablage nicht', function () {
    $first = User::factory()->create();
    $second = User::factory()->create();
    $b = neueBestellung();

    $b->markAbgelegt($first);
    $zeit = $b->fresh()->abgelegt_at;
    $this->travel(5)->minutes();
    $b->markAbgelegt($second);

    $b->refresh();
    expect($b->abgelegt_by)->toBe($first->id)
        ->and($b->abgelegt_at->equalTo($zeit))->toBeTrue();
    expect(Activity::where('log_name', 'bestellung')->where('event', 'abgelegt')->count())->toBe(1);
});
