<?php

use App\Models\Item;
use App\Models\Mietvorgang;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;

function rentalItem(): Item
{
    $unit = Unit::create(['bezeichnung' => 'Kameras']);
    $supplier = Supplier::create(['bezeichnung' => 'Pixmade']);

    return Item::create(['units_id' => $unit->id, 'bezeichnung' => 'Miet-Kamera', 'suppliers_id' => $supplier->id]);
}

function mietVorgang(Item $item, string $start, string $end, bool $complete = false): Mietvorgang
{
    $mv = Mietvorgang::create([
        'suppliers_id' => $item->suppliers_id, 'rent_start' => $start, 'rent_end' => $end,
        'transport_end_confirmed_at' => $complete ? now() : null,
        'bereit_zur_rueckgabe_confirmed_at' => $complete ? now() : null,
    ]);
    $mv->items()->attach($item->id);

    return $mv;
}

beforeEach(fn () => $this->travelTo('2026-12-10'));

test('ohne Mietvorgang: weder Archiv noch offene Rückgabe', function () {
    $item = rentalItem()->load('mietvorgaenge');

    expect($item->isRentArchived())->toBeFalse()
        ->and($item->hasOpenReturn())->toBeFalse()
        ->and($item->displayMietvorgang())->toBeNull();
});

test('alle abgeschlossen ergibt Archiv und zeigt den Vorgang mit dem späteren Ende', function () {
    $item = rentalItem();
    mietVorgang($item, '2026-01-01', '2026-01-05', true);
    $spaeter = mietVorgang($item, '2026-03-01', '2026-03-05', true);

    $item->load('mietvorgaenge');

    expect($item->isRentArchived())->toBeTrue()
        ->and($item->displayMietvorgang()->id)->toBe($spaeter->id);
});

test('ein offener Mietvorgang verhindert das Archiv', function () {
    $item = rentalItem();
    mietVorgang($item, '2026-01-01', '2026-01-05', true);
    mietVorgang($item, '2026-12-20', '2026-12-24', false);

    expect($item->load('mietvorgaenge')->isRentArchived())->toBeFalse();
});

test('abgelaufen aber nicht zurückgegeben bleibt aktuell und ist "Rückgabe offen"', function () {
    $item = rentalItem();
    $mv = mietVorgang($item, '2026-11-01', '2026-11-05', false);

    $item->load('mietvorgaenge');
    expect($item->isRentArchived())->toBeFalse()->and($item->hasOpenReturn())->toBeTrue();

    $mv->update(['transport_end_confirmed_at' => now(), 'bereit_zur_rueckgabe_confirmed_at' => now()]);
    $item->load('mietvorgaenge');
    expect($item->hasOpenReturn())->toBeFalse()->and($item->isRentArchived())->toBeTrue();
});

test('die Anzeige wählt laufend vor kommend vor abgelaufen', function () {
    $item = rentalItem();
    $abgelaufen = mietVorgang($item, '2026-11-01', '2026-11-05');
    $kommend = mietVorgang($item, '2027-01-10', '2027-01-15');
    $laufend = mietVorgang($item, '2026-12-08', '2026-12-12');

    expect($item->load('mietvorgaenge')->displayMietvorgang()->id)->toBe($laufend->id);

    $laufend->items()->detach($item->id);
    expect($item->load('mietvorgaenge')->displayMietvorgang()->id)->toBe($kommend->id);

    $kommend->items()->detach($item->id);
    $aelter = mietVorgang($item, '2026-09-01', '2026-09-03');
    expect($item->load('mietvorgaenge')->displayMietvorgang()->id)->toBe($abgelaufen->id);
});

test('Mischfall: alter abgeschlossener plus kommender offener ist aktuell und zeigt den kommenden', function () {
    $item = rentalItem();
    mietVorgang($item, '2026-01-01', '2026-01-05', true);
    $kommend = mietVorgang($item, '2026-12-20', '2026-12-24');

    $item->load('mietvorgaenge');

    expect($item->isRentArchived())->toBeFalse()
        ->and($item->displayMietvorgang()->id)->toBe($kommend->id);
});

test('bei geladener Relation braucht keine Methode eine weitere Abfrage', function () {
    $item = rentalItem();
    mietVorgang($item, '2026-01-01', '2026-01-05', true);
    $item->load('mietvorgaenge');

    DB::enableQueryLog();
    $item->isRentArchived();
    $item->hasOpenReturn();
    $item->displayMietvorgang();

    expect(DB::getQueryLog())->toBe([]);
});
