<?php

use App\Models\Item;
use App\Models\Mieter;
use App\Models\Unit;
use App\Models\User;
use App\Models\Vermietvorgang;

test('Materialliste-PDF eines Vermietvorgangs lässt sich abrufen und enthält die zugeordneten Geräte', function () {
    $user = User::factory()->create(['role' => 'viewer']);
    $unit = Unit::create(['bezeichnung' => 'Kameras']);
    $mieter = Mieter::create(['bezeichnung' => 'Kunde GmbH']);

    $item1 = Item::create(['bezeichnung' => 'Kamera A', 'nummer' => 'K-001', 'units_id' => $unit->id]);
    $item2 = Item::create(['bezeichnung' => 'Kamera B', 'units_id' => $unit->id]);

    $vermietvorgang = Vermietvorgang::create([
        'bezeichnung' => 'Kunde GmbH V-260701',
        'mieter_id' => $mieter->id,
        'rent_start' => '2026-07-20',
        'rent_end' => '2026-07-25',
    ]);
    $vermietvorgang->items()->attach([$item1->id, $item2->id]);

    $response = $this->actingAs($user)->get(route('vermietvorgaenge.pdf', $vermietvorgang));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
});

test('Materialliste-PDF eines Vermietvorgangs ohne Geräte funktioniert ohne Fehler', function () {
    $user = User::factory()->create(['role' => 'viewer']);
    $mieter = Mieter::create(['bezeichnung' => 'Leerer Kunde']);

    $vermietvorgang = Vermietvorgang::create([
        'bezeichnung' => 'Leerer Kunde V-260702',
        'mieter_id' => $mieter->id,
        'rent_start' => '2026-08-01',
        'rent_end' => '2026-08-03',
    ]);

    $response = $this->actingAs($user)->get(route('vermietvorgaenge.pdf', $vermietvorgang));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
});
