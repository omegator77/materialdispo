<?php

use App\Models\Item;
use App\Models\Production;
use App\Models\Supplier;
use App\Models\Unit;
use App\Services\ProductionAssignmentService;

function assignmentProduction(): Production
{
    return Production::create(['bezeichnung' => 'Prod', 'booking_start' => '2026-08-01', 'booking_end' => '2026-08-02']);
}

test('attachMany meldet eine Warnung für Mietgeräte ohne Mietvorgang', function () {
    $unit = Unit::create(['bezeichnung' => 'Kameras']);
    $supplier = Supplier::create(['bezeichnung' => 'Verleih']);
    $gemietet = Item::create(['units_id' => $unit->id, 'bezeichnung' => 'Miet-Kamera', 'suppliers_id' => $supplier->id]);
    $eigen = Item::create(['units_id' => $unit->id, 'bezeichnung' => 'Eigene Kamera']);
    $production = assignmentProduction();

    $result = app(ProductionAssignmentService::class)->attachMany($production, [$gemietet, $eigen]);

    expect($result['added'])->toHaveCount(2)
        ->and($result['warnings'])->toBe(['Miet-Kamera (noch nicht gemietet)']);
});

test('attachMany liefert ohne Warnungen eine leere Liste', function () {
    $unit = Unit::create(['bezeichnung' => 'Kameras']);
    $eigen = Item::create(['units_id' => $unit->id, 'bezeichnung' => 'Eigene Kamera']);

    $result = app(ProductionAssignmentService::class)->attachMany(assignmentProduction(), [$eigen]);

    expect($result['warnings'])->toBe([]);
});
