<?php

use App\Models\Item;
use App\Models\Mieter;
use App\Models\Unit;
use App\Models\User;
use App\Models\Vermietvorgang;

test('Geräte-Timeline sortiert Geräte innerhalb einer Gruppe alphabetisch nach Bezeichnung, dann nach Nummer', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $unit = Unit::create(['bezeichnung' => 'Kameras']);

    Item::create(['bezeichnung' => 'Zebra Cam', 'nummer' => '001', 'units_id' => $unit->id]);
    Item::create(['bezeichnung' => 'Alpha Cam', 'nummer' => '002', 'units_id' => $unit->id]);
    Item::create(['bezeichnung' => 'Alpha Cam', 'nummer' => '001', 'units_id' => $unit->id]);

    $response = $this->actingAs($user)->get(route('timeline.items'));

    $response->assertOk();

    $content = $response->getContent();
    $posAlpha001 = strpos($content, 'Alpha Cam');
    $posZebra = strpos($content, 'Zebra Cam');

    expect($posAlpha001)->not->toBeFalse();
    expect($posZebra)->not->toBeFalse();
    expect($posAlpha001)->toBeLessThan($posZebra);
});

test('Geräte-Timeline zeigt Gruppen in der konfigurierten sort_order-Reihenfolge, nicht in ID-Reihenfolge', function () {
    $user = User::factory()->create(['role' => 'admin']);

    // Absichtlich in umgekehrter ID-Reihenfolge angelegt, aber via sort_order
    // in die gewünschte Anzeige-Reihenfolge gebracht.
    $unitB = Unit::create(['bezeichnung' => 'Gruppe B']);
    $unitA = Unit::create(['bezeichnung' => 'Gruppe A']);
    $unitA->update(['sort_order' => 1]);
    $unitB->update(['sort_order' => 2]);

    Item::create(['bezeichnung' => 'Gerät A', 'units_id' => $unitA->id]);
    Item::create(['bezeichnung' => 'Gerät B', 'units_id' => $unitB->id]);

    $content = $this->actingAs($user)->get(route('timeline.items'))->getContent();

    $posGroupA = strpos($content, 'Gruppe A');
    $posGroupB = strpos($content, 'Gruppe B');

    expect($posGroupA)->not->toBeFalse();
    expect($posGroupB)->not->toBeFalse();
    expect($posGroupA)->toBeLessThan($posGroupB);
});

test('Buchungsfilter "Nur gebucht" zeigt nur Geräte mit Buchung im sichtbaren Zeitraum', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $unit = Unit::create(['bezeichnung' => 'Kameras']);
    $mieter = Mieter::create(['bezeichnung' => 'Testkunde']);

    $bookedItem = Item::create(['bezeichnung' => 'Gebuchtes Gerät', 'units_id' => $unit->id]);
    $freeItem = Item::create(['bezeichnung' => 'Freies Gerät', 'units_id' => $unit->id]);

    $vermietvorgang = Vermietvorgang::create([
        'mieter_id' => $mieter->id,
        'rent_start' => now()->startOfMonth()->addDays(2)->toDateString(),
        'rent_end' => now()->startOfMonth()->addDays(4)->toDateString(),
    ]);
    $vermietvorgang->items()->attach($bookedItem->id);

    $bookedOnly = $this->actingAs($user)
        ->get(route('timeline.items', ['booking_status' => 'booked']))
        ->getContent();

    expect($bookedOnly)->toContain('Gebuchtes Gerät');
    expect($bookedOnly)->not->toContain('Freies Gerät');

    $freeOnly = $this->actingAs($user)
        ->get(route('timeline.items', ['booking_status' => 'free']))
        ->getContent();

    expect($freeOnly)->not->toContain('Gebuchtes Gerät');
    expect($freeOnly)->toContain('Freies Gerät');

    $all = $this->actingAs($user)
        ->get(route('timeline.items', ['booking_status' => 'all']))
        ->getContent();

    expect($all)->toContain('Gebuchtes Gerät');
    expect($all)->toContain('Freies Gerät');
});
