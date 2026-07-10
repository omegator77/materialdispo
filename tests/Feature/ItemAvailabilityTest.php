<?php

use App\Models\Item;
use App\Models\Mietvorgang;
use App\Models\Production;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Services\ItemAvailabilityService;

function makeUnit(): Unit
{
    return Unit::create(['bezeichnung' => 'Kameras']);
}

function makeProduction(string $start, string $end, string $name = 'Produktion'): Production
{
    return Production::create([
        'bezeichnung' => $name,
        'booking_start' => $start,
        'booking_end' => $end,
    ]);
}

function makeItem(Unit $unit, array $overrides = []): Item
{
    return Item::create(array_merge([
        'units_id' => $unit->id,
        'bezeichnung' => 'Testgerät',
    ], $overrides));
}

function attachMietvorgang(Item $item, Supplier $supplier, string $start, string $end): Mietvorgang
{
    $mietvorgang = Mietvorgang::create([
        'suppliers_id' => $supplier->id,
        'rent_start' => $start,
        'rent_end' => $end,
    ]);

    $mietvorgang->items()->attach($item->id);

    return $mietvorgang;
}

test('item is available when no other booking exists', function () {
    $unit = makeUnit();
    $item = makeItem($unit);
    $production = makeProduction('2026-07-01', '2026-07-05');

    $result = app(ItemAvailabilityService::class)->check($item, $production);

    expect($result['available'])->toBeTrue();
});

test('item is unavailable when booked in an overlapping production', function () {
    $unit = makeUnit();
    $item = makeItem($unit);

    $existing = makeProduction('2026-07-01', '2026-07-10', 'Bestehende Produktion');
    $existing->items()->attach($item->id);

    $newProduction = makeProduction('2026-07-05', '2026-07-15', 'Neue Produktion');

    $result = app(ItemAvailabilityService::class)->check($item, $newProduction);

    expect($result['available'])->toBeFalse()
        ->and($result['reason'])->toContain('Bestehende Produktion');
});

test('item is available when booked in a non-overlapping production', function () {
    $unit = makeUnit();
    $item = makeItem($unit);

    $existing = makeProduction('2026-07-01', '2026-07-05', 'Bestehende Produktion');
    $existing->items()->attach($item->id);

    $newProduction = makeProduction('2026-07-10', '2026-07-15', 'Neue Produktion');

    $result = app(ItemAvailabilityService::class)->check($item, $newProduction);

    expect($result['available'])->toBeTrue();
});

test('rented item is unavailable when rental starts after production start', function () {
    $unit = makeUnit();
    $supplier = Supplier::create([
        'bezeichnung' => 'Mietpartner',
        'kontakt' => 'Max Mustermann',
        'phone' => '0123456789',
    ]);

    $item = makeItem($unit, ['suppliers_id' => $supplier->id]);
    attachMietvorgang($item, $supplier, '2026-07-03', '2026-07-20');

    $production = makeProduction('2026-07-01', '2026-07-10');

    $result = app(ItemAvailabilityService::class)->check($item, $production);

    expect($result['available'])->toBeFalse()
        ->and($result['reason'])->toBe('Mietbeginn zu spät');
});

test('rented item is unavailable when rental ends before production end', function () {
    $unit = makeUnit();
    $supplier = Supplier::create([
        'bezeichnung' => 'Mietpartner',
        'kontakt' => 'Max Mustermann',
        'phone' => '0123456789',
    ]);

    $item = makeItem($unit, ['suppliers_id' => $supplier->id]);
    attachMietvorgang($item, $supplier, '2026-06-25', '2026-07-08');

    $production = makeProduction('2026-07-01', '2026-07-10');

    $result = app(ItemAvailabilityService::class)->check($item, $production);

    expect($result['available'])->toBeFalse()
        ->and($result['reason'])->toBe('Mietende zu früh');
});

test('rented item is available when rental window fully covers the production', function () {
    $unit = makeUnit();
    $supplier = Supplier::create([
        'bezeichnung' => 'Mietpartner',
        'kontakt' => 'Max Mustermann',
        'phone' => '0123456789',
    ]);

    $item = makeItem($unit, ['suppliers_id' => $supplier->id]);
    attachMietvorgang($item, $supplier, '2026-06-25', '2026-07-20');

    $production = makeProduction('2026-07-01', '2026-07-10');

    $result = app(ItemAvailabilityService::class)->check($item, $production);

    expect($result['available'])->toBeTrue();
});

test('attaching an item via the controller respects availability and rejects double booking', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $unit = makeUnit();
    $item = makeItem($unit);

    $existing = makeProduction('2026-08-01', '2026-08-10', 'Bestehende Produktion');
    $existing->items()->attach($item->id);

    $newProduction = makeProduction('2026-08-05', '2026-08-15', 'Neue Produktion');

    $response = $this->actingAs($admin)->post(route('productions.attachItem', $newProduction->id), [
        'item_id' => $item->id,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');

    expect($newProduction->items()->where('items.id', $item->id)->exists())->toBeFalse();
});

test('attaching multiple items in one request adds available ones and skips conflicting ones', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $unit = makeUnit();

    $freeItem = makeItem($unit, ['bezeichnung' => 'Freies Gerät']);
    $bookedItem = makeItem($unit, ['bezeichnung' => 'Gebuchtes Gerät']);

    $conflicting = makeProduction('2026-09-01', '2026-09-10', 'Konflikt-Produktion');
    $conflicting->items()->attach($bookedItem->id);

    $production = makeProduction('2026-09-05', '2026-09-15', 'Zielproduktion');

    $response = $this->actingAs($admin)->post(route('productions.attachItem', $production->id), [
        'item_id' => [$freeItem->id, $bookedItem->id],
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect($production->items()->where('items.id', $freeItem->id)->exists())->toBeTrue()
        ->and($production->items()->where('items.id', $bookedItem->id)->exists())->toBeFalse();
});
