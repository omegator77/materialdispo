<?php

use App\Models\CameraConfig;
use App\Models\Item;
use App\Models\Mieter;
use App\Models\Mietvorgang;
use App\Models\Production;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Vermietvorgang;
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

/*
|--------------------------------------------------------------------------
| Mietgeräte ohne Mietvorgang: vorläufig planbar (mit Warnung)
|--------------------------------------------------------------------------
*/

function makeRentalItem(Unit $unit, ?Supplier &$supplier = null): Item
{
    $supplier ??= Supplier::create(['bezeichnung' => 'Mietpartner']);

    return makeItem($unit, ['suppliers_id' => $supplier->id]);
}

test('Mietgerät ohne Mietvorgang ist für Produktionen planbar, liefert aber eine Warnung', function () {
    $item = makeRentalItem(makeUnit());
    $production = makeProduction('2026-07-01', '2026-07-05');

    $result = app(ItemAvailabilityService::class)->check($item, $production);

    expect($result['available'])->toBeTrue()
        ->and($result['warning'])->toContain('nicht gemietet');
});

test('eigene Geräte und gedeckte Mietgeräte liefern keine Warnung', function () {
    $unit = makeUnit();
    $production = makeProduction('2026-07-01', '2026-07-05');

    $own = makeItem($unit);
    $supplier = null;
    $rented = makeRentalItem($unit, $supplier);
    attachMietvorgang($rented, $supplier, '2026-06-28', '2026-07-10');

    $service = app(ItemAvailabilityService::class);

    expect($service->check($own, $production)['warning'])->toBeNull()
        ->and($service->check($rented, $production)['warning'])->toBeNull();
});

test('Mietvorgang kann an ein bereits verplantes Gerät angehängt werden, wenn er die Produktion abdeckt', function () {
    $supplier = null;
    $item = makeRentalItem(makeUnit(), $supplier);
    makeProduction('2026-07-10', '2026-07-15', 'Bestehende Produktion')->items()->attach($item->id);

    $mietvorgang = Mietvorgang::create(['suppliers_id' => $supplier->id, 'rent_start' => '2026-07-08', 'rent_end' => '2026-07-20']);

    $result = app(ItemAvailabilityService::class)->checkForMiete($item, $mietvorgang);

    expect($result['available'])->toBeTrue();
});

test('Mietvorgang wird abgelehnt, wenn er eine bereits verplante Produktion nicht vollständig abdeckt', function () {
    $supplier = null;
    $item = makeRentalItem(makeUnit(), $supplier);
    makeProduction('2026-07-10', '2026-07-15', 'Bestehende Produktion')->items()->attach($item->id);

    $mietvorgang = Mietvorgang::create(['suppliers_id' => $supplier->id, 'rent_start' => '2026-07-12', 'rent_end' => '2026-07-20']);

    $result = app(ItemAvailabilityService::class)->checkForMiete($item, $mietvorgang);

    expect($result['available'])->toBeFalse()
        ->and($result['reason'])->toContain('Bestehende Produktion')->toContain('deckt');
});

test('Mietvorgang deckt auch Kamerakonfigurations-Produktionen ab oder wird abgelehnt', function () {
    $supplier = null;
    $item = makeRentalItem(makeUnit(), $supplier);
    $production = makeProduction('2026-07-10', '2026-07-15', 'Kamera-Produktion');
    CameraConfig::create(['production_id' => $production->id, 'item_id' => $item->id, 'cam_number' => 'Cam 1']);

    $service = app(ItemAvailabilityService::class);
    $deckt = Mietvorgang::create(['suppliers_id' => $supplier->id, 'rent_start' => '2026-07-10', 'rent_end' => '2026-07-15']);
    $deckNicht = Mietvorgang::create(['suppliers_id' => $supplier->id, 'rent_start' => '2026-07-11', 'rent_end' => '2026-07-15']);

    expect($service->checkForMiete($item, $deckt)['available'])->toBeTrue()
        ->and($service->checkForMiete($item, $deckNicht)['available'])->toBeFalse();
});

test('Vermietvorgang bleibt bei Produktionsüberschneidung blockiert', function () {
    $item = makeItem(makeUnit());
    makeProduction('2026-07-10', '2026-07-15', 'Bestehende Produktion')->items()->attach($item->id);
    $mieter = Mieter::create(['bezeichnung' => 'Kunde']);
    $vv = Vermietvorgang::create(['mieter_id' => $mieter->id, 'rent_start' => '2026-07-01', 'rent_end' => '2026-07-30']);

    expect(app(ItemAvailabilityService::class)->checkForVerleih($item, $vv)['available'])->toBeFalse();
});

test('Produktion mit ungemietetem Mietgerät zeigt beim Hinzufügen einen Hinweis', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $item = makeRentalItem(makeUnit());
    $production = makeProduction('2026-07-01', '2026-07-05');

    $response = $this->actingAs($admin)->post(route('productions.attachItem', $production->id), ['item_id' => [$item->id]]);

    $response->assertSessionHas('success')->assertSessionHas('warning');
    expect(session('warning'))->toContain('nicht gemietet')
        ->and($production->items()->where('items.id', $item->id)->exists())->toBeTrue();
});
