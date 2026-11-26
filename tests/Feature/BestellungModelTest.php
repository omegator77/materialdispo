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
        'bestellt_am' => '2026-11-20 14:35:00',
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
    expect(Activity::where('log_name', 'bestellung')->where('causer_id', $first->id)->count())->toBe(1);
    expect(Activity::where('subject_type', Bestellung::class)->where('subject_id', $b->id)->where('event', 'updated')->count())->toBe(0);
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

test('bestellt_am behält die Uhrzeit', function () {
    $b = Bestellung::create(['bestellt_bei' => 'X', 'bestellt_was' => 'Y', 'bezahlt_mit' => 'Z', 'bestellt_am' => '2026-11-20 14:35:00'])->fresh();

    expect($b->bestellt_am->format('d.m.Y H:i'))->toBe('20.11.2026 14:35');
});

test('Stufenwechsel ohne angemeldete Sitzung protokolliert genau einen Eintrag mit Verursacher', function () {
    $user = User::factory()->create();
    $b = neueBestellung();
    Activity::query()->delete();

    $b->markAbgelegt($user);

    $entries = Activity::where('subject_id', $b->id)->get();
    expect($entries)->toHaveCount(1)
        ->and($entries->first()->causer_id)->toBe($user->id);

    Activity::query()->delete();
    $b->revertStage($user);
    $entries = Activity::where('subject_id', $b->id)->get();
    expect($entries)->toHaveCount(1)->and($entries->first()->causer_id)->toBe($user->id);
});

test('markRechnungVorhanden und markAbgelegt liefern true, dann false', function () {
    $user = User::factory()->create();
    $b = neueBestellung();

    expect($b->markRechnungVorhanden($user))->toBeTrue()
        ->and($b->markRechnungVorhanden($user))->toBeFalse();

    $c = neueBestellung();
    expect($c->markAbgelegt($user))->toBeTrue()
        ->and($c->markAbgelegt($user))->toBeFalse();
});

test('markRechnungVorhanden auf bereits abgelegter Bestellung gibt false und ändert nichts', function () {
    $user = User::factory()->create();
    $b = neueBestellung();
    $b->markAbgelegt($user);
    $abgelegtAt = $b->fresh()->abgelegt_at;

    expect($b->markRechnungVorhanden($user))->toBeFalse();
    $b->refresh();
    expect($b->rechnung_vorhanden_at)->toBeNull()->and($b->abgelegt_at->equalTo($abgelegtAt))->toBeTrue();
});

test('veraltete zweite Instanz löst keinen zweiten Stufenwechsel aus', function () {
    $user = User::factory()->create();
    $b = neueBestellung();
    $stale = Bestellung::find($b->id);

    expect($b->markRechnungVorhanden($user))->toBeTrue();
    Activity::query()->delete();
    expect($stale->markRechnungVorhanden($user))->toBeFalse();
    expect(Activity::count())->toBe(0);
});

test('markAngekommen setzt Zeit, Person und getrimmte Notiz und ist nicht überschreibbar', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $b = neueBestellung();

    expect($b->markAngekommen($user, '  Regal 3  '))->toBeTrue();
    $b->refresh();
    $zeit = $b->angekommen_at;

    expect($zeit)->not->toBeNull()
        ->and($b->angekommen_by)->toBe($user->id)
        ->and($b->angekommen_notiz)->toBe('Regal 3')
        ->and($b->istAngekommen())->toBeTrue()
        ->and($b->angekommenVon->is($user))->toBeTrue();

    expect($b->markAngekommen($other, 'Anderes'))->toBeFalse();
    $b->refresh();
    expect($b->angekommen_at->equalTo($zeit))->toBeTrue()
        ->and($b->angekommen_by)->toBe($user->id)
        ->and($b->angekommen_notiz)->toBe('Regal 3');
});

test('markAngekommen speichert leere Notiz als null und kürzt auf 255 Zeichen', function (?string $notiz) {
    $user = User::factory()->create();
    $b = neueBestellung();

    $b->markAngekommen($user, $notiz);

    expect($b->fresh()->angekommen_notiz)->toBeNull();
})->with([null, '', '   ']);

test('markAngekommen kürzt lange Notiz auf 255 Zeichen', function () {
    $user = User::factory()->create();
    $b = neueBestellung();

    $b->markAngekommen($user, str_repeat('ä', 300));

    expect(mb_strlen($b->fresh()->angekommen_notiz))->toBe(255);
});

test('Ankunft ändert die Stufe nicht', function () {
    $user = User::factory()->create();
    $b = neueBestellung(['sendungsnummer' => '123']);
    expect($b->stufe())->toBe(Bestellung::STUFE_UNTERWEGS);

    $b->markAngekommen($user);
    expect($b->stufe())->toBe(Bestellung::STUFE_UNTERWEGS);

    $c = neueBestellung();
    $c->markAngekommen($user);
    expect($c->stufe())->toBe(Bestellung::STUFE_BESTELLT);
});

test('markAngekommen protokolliert genau einen Eintrag mit Verursacher', function () {
    $user = User::factory()->create();
    $b = neueBestellung();
    Activity::query()->delete();

    $b->markAngekommen($user, 'Regal 3');
    $b->markAngekommen($user, 'Regal 4');

    $entries = Activity::where('subject_id', $b->id)->get();
    expect($entries)->toHaveCount(1)
        ->and($entries->first()->event)->toBe('angekommen')
        ->and($entries->first()->causer_id)->toBe($user->id)
        ->and($entries->first()->description)->toContain('Sendung angekommen')
        ->and($entries->first()->description)->toContain('(Notiz: Regal 3)');
});

test('revertAngekommen leert Spalten, protokolliert einmal und erlaubt erneutes Eintragen', function () {
    $user = User::factory()->create();
    $b = neueBestellung();

    expect($b->revertAngekommen($user))->toBeFalse();

    $b->markAngekommen($user, 'Regal 3');
    Activity::query()->delete();

    expect($b->revertAngekommen($user))->toBeTrue();
    $b->refresh();
    expect($b->angekommen_at)->toBeNull()
        ->and($b->angekommen_by)->toBeNull()
        ->and($b->angekommen_notiz)->toBeNull()
        ->and($b->istAngekommen())->toBeFalse();

    $entries = Activity::where('subject_id', $b->id)->get();
    expect($entries)->toHaveCount(1)
        ->and($entries->first()->event)->toBe('angekommen_zurueckgenommen')
        ->and($entries->first()->causer_id)->toBe($user->id);

    expect($b->revertAngekommen($user))->toBeFalse();
    expect($b->markAngekommen($user, 'Neu'))->toBeTrue()
        ->and($b->fresh()->angekommen_notiz)->toBe('Neu');
});
