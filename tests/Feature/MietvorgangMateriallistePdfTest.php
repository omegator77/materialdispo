<?php

use App\Models\Item;
use App\Models\Mietvorgang;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;

test('Materialliste-PDF eines Mietvorgangs lässt sich abrufen und enthält die zugeordneten Geräte', function () {
    $user = User::factory()->create(['role' => 'viewer']);
    $unit = Unit::create(['bezeichnung' => 'Kameras']);
    $supplier = Supplier::create(['bezeichnung' => 'Verleiher GmbH']);

    $item1 = Item::create(['bezeichnung' => 'Kamera A', 'nummer' => 'K-001', 'units_id' => $unit->id, 'suppliers_id' => $supplier->id]);
    $item2 = Item::create(['bezeichnung' => 'Kamera B', 'units_id' => $unit->id, 'suppliers_id' => $supplier->id]);

    $mietvorgang = Mietvorgang::create([
        'bezeichnung' => 'Verleiher GmbH M-260701',
        'suppliers_id' => $supplier->id,
        'rent_start' => '2026-07-20',
        'rent_end' => '2026-07-25',
    ]);
    $mietvorgang->items()->attach([$item1->id, $item2->id]);

    $response = $this->actingAs($user)->get(route('mietvorgaenge.pdf', $mietvorgang));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
});

test('Materialliste-PDF eines Mietvorgangs ohne Geräte funktioniert ohne Fehler', function () {
    $user = User::factory()->create(['role' => 'viewer']);
    $supplier = Supplier::create(['bezeichnung' => 'Leerer Verleiher']);

    $mietvorgang = Mietvorgang::create([
        'bezeichnung' => 'Leerer Verleiher M-260702',
        'suppliers_id' => $supplier->id,
        'rent_start' => '2026-08-01',
        'rent_end' => '2026-08-03',
    ]);

    $response = $this->actingAs($user)->get(route('mietvorgaenge.pdf', $mietvorgang));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
});
