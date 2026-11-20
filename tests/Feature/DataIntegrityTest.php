<?php

use App\Models\Item;
use App\Models\Mieter;
use App\Models\Mietvorgang;
use App\Models\Production;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\VbProtokoll;
use App\Models\VbProtokollAnforderung;
use App\Models\VbProtokollFreitextBlock;
use App\Models\Vermietvorgang;
use App\Services\VbProtokollAnforderungSyncService;
use App\Services\VbProtokollFreitextBlockSyncService;
use Illuminate\Support\Facades\Event;

function integrityProtokoll(): VbProtokoll
{
    $production = Production::create(['bezeichnung' => 'P', 'booking_start' => '2026-08-01', 'booking_end' => '2026-08-02']);

    return VbProtokoll::forceCreate(['production_id' => $production->id]);
}

test('Anforderungen-Sync lässt bei einem Fehler die alten Anforderungen unverändert', function () {
    $protokoll = integrityProtokoll();
    $unit = Unit::create(['bezeichnung' => 'Kameras']);
    $protokoll->anforderungen()->create(['unit_id' => $unit->id, 'anzahl' => 2, 'notiz' => 'alt']);

    $event = 'eloquent.creating: '.VbProtokollAnforderung::class;
    $calls = 0;
    Event::listen($event, function () use (&$calls) {
        if (++$calls === 2) {
            throw new RuntimeException('Simulierter Fehler');
        }
    });

    try {
        app(VbProtokollAnforderungSyncService::class)->sync($protokoll, [
            ['unit_id' => $unit->id, 'anzahl' => 1, 'notiz' => 'neu 1'],
            ['unit_id' => $unit->id, 'anzahl' => 3, 'notiz' => 'neu 2'],
        ]);
    } catch (RuntimeException) {
    } finally {
        Event::forget($event);
    }

    expect($protokoll->anforderungen()->pluck('notiz')->all())->toBe(['alt']);
});

test('Freitextblock-Sync lässt bei einem Fehler die alten Blöcke unverändert', function () {
    $protokoll = integrityProtokoll();
    $protokoll->freitextBloecke()->create(['ueberschrift' => 'Alt', 'text' => 'bleibt', 'sort_order' => 0]);

    $event = 'eloquent.creating: '.VbProtokollFreitextBlock::class;
    $calls = 0;
    Event::listen($event, function () use (&$calls) {
        if (++$calls === 2) {
            throw new RuntimeException('Simulierter Fehler');
        }
    });

    try {
        app(VbProtokollFreitextBlockSyncService::class)->sync($protokoll, [
            ['ueberschrift' => 'Neu 1', 'text' => 'a'],
            ['ueberschrift' => 'Neu 2', 'text' => 'b'],
        ]);
    } catch (RuntimeException) {
    } finally {
        Event::forget($event);
    }

    expect($protokoll->freitextBloecke()->pluck('ueberschrift')->all())->toBe(['Alt']);
});

test('Abgelehnte Schnellzuordnung hinterlässt keinen leeren Mietvorgang', function () {
    $supplier = Supplier::create(['bezeichnung' => 'Verleih']);
    $item = Item::create(['units_id' => Unit::create(['bezeichnung' => 'K'])->id, 'bezeichnung' => 'Kamera', 'suppliers_id' => $supplier->id]);
    Production::create(['bezeichnung' => 'Bestehende', 'booking_start' => '2026-07-10', 'booking_end' => '2026-07-15'])
        ->items()->attach($item->id);

    // Zeitraum überschneidet die Produktion, deckt sie aber nicht ab -> abgelehnt.
    $result = $item->syncMietvorgang('2026-07-12', '2026-07-20');

    expect($result['added'])->toBeFalse()
        ->and(Mietvorgang::count())->toBe(0);
});

test('Abgelehnte Schnellzuordnung löscht einen bereits bestehenden Mietvorgang nicht', function () {
    $supplier = Supplier::create(['bezeichnung' => 'Verleih']);
    $item = Item::create(['units_id' => Unit::create(['bezeichnung' => 'K'])->id, 'bezeichnung' => 'Kamera', 'suppliers_id' => $supplier->id]);
    $bestehend = Mietvorgang::create(['suppliers_id' => $supplier->id, 'rent_start' => '2026-07-12', 'rent_end' => '2026-07-20']);
    Production::create(['bezeichnung' => 'Bestehende', 'booking_start' => '2026-07-10', 'booking_end' => '2026-07-15'])
        ->items()->attach($item->id);

    $item->syncMietvorgang('2026-07-12', '2026-07-20');

    expect(Mietvorgang::whereKey($bestehend->id)->exists())->toBeTrue();
});

test('Abgelehnte Schnellzuordnung hinterlässt keinen leeren Vermietvorgang', function () {
    $item = Item::create(['units_id' => Unit::create(['bezeichnung' => 'K'])->id, 'bezeichnung' => 'Kamera']);
    $mieter = Mieter::create(['bezeichnung' => 'Kunde']);
    Production::create(['bezeichnung' => 'Bestehende', 'booking_start' => '2026-07-10', 'booking_end' => '2026-07-15'])
        ->items()->attach($item->id);

    $result = $item->syncVermietvorgang($mieter->id, '2026-07-12', '2026-07-20');

    expect($result['added'])->toBeFalse()
        ->and(Vermietvorgang::count())->toBe(0);
});

test('Referenzcodes bleiben nach dem Löschen eines Mietvorgangs eindeutig', function () {
    $supplier = Supplier::create(['bezeichnung' => 'Verleih']);

    $erster = Mietvorgang::findOrCreateFor($supplier->id, '2026-09-01', '2026-09-02');
    $zweiter = Mietvorgang::findOrCreateFor($supplier->id, '2026-09-03', '2026-09-04');
    $erster->delete();
    $dritter = Mietvorgang::findOrCreateFor($supplier->id, '2026-09-05', '2026-09-06');

    expect($dritter->bezeichnung)->not->toBe($zweiter->bezeichnung);
});

test('Referenzcodes bleiben nach dem Löschen eines Vermietvorgangs eindeutig', function () {
    $mieter = Mieter::create(['bezeichnung' => 'Kunde']);

    $erster = Vermietvorgang::findOrCreateFor($mieter->id, '2026-09-01', '2026-09-02');
    $zweiter = Vermietvorgang::findOrCreateFor($mieter->id, '2026-09-03', '2026-09-04');
    $erster->delete();
    $dritter = Vermietvorgang::findOrCreateFor($mieter->id, '2026-09-05', '2026-09-06');

    expect($dritter->bezeichnung)->not->toBe($zweiter->bezeichnung);
});
