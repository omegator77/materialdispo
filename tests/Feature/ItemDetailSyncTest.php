<?php

use App\Models\CameraDetail;
use App\Models\Item;
use App\Models\LensDetail;
use App\Models\MonitorDetail;
use App\Models\Unit;
use App\Models\User;

test('creating a camera item does not create camera details yet, but updating it does', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $cameraUnit = Unit::create(['bezeichnung' => 'Kameras']);

    $this->actingAs($admin)->post(route('items.store'), [
        'units_id' => $cameraUnit->id,
        'bezeichnung' => 'Kamera 1',
        'body_serial' => 'SN-123',
    ])->assertRedirect(route('items.index'));

    $item = Item::where('bezeichnung', 'Kamera 1')->firstOrFail();

    // Bestehende Eigenheit: store() synct camera_details NICHT.
    expect(CameraDetail::where('item_id', $item->id)->exists())->toBeFalse();

    $this->actingAs($admin)->put(route('items.update', $item), [
        'units_id' => $cameraUnit->id,
        'bezeichnung' => 'Kamera 1',
        'body_serial' => 'SN-123',
    ])->assertRedirect(route('items.index'));

    $detail = CameraDetail::where('item_id', $item->id)->first();
    expect($detail)->not->toBeNull()
        ->and($detail->body_serial)->toBe('SN-123');
});

test('creating a monitor item syncs monitor details immediately', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $monitorUnit = Unit::create(['bezeichnung' => 'Monitore bis 24 Zoll']);

    $this->actingAs($admin)->post(route('items.store'), [
        'units_id' => $monitorUnit->id,
        'bezeichnung' => 'Monitor 1',
        'manufacturer' => 'Sony',
        'model' => 'PVM-A250',
    ])->assertRedirect(route('items.index'));

    $item = Item::where('bezeichnung', 'Monitor 1')->firstOrFail();

    $detail = MonitorDetail::where('item_id', $item->id)->first();
    expect($detail)->not->toBeNull()
        ->and($detail->manufacturer)->toBe('Sony');
});

test('switching an item unit type deletes the stale detail record and creates the new one', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $monitorUnit = Unit::create(['bezeichnung' => 'Monitore bis 24 Zoll']);
    $lensUnit = Unit::create(['bezeichnung' => 'Objektive']);

    $this->actingAs($admin)->post(route('items.store'), [
        'units_id' => $monitorUnit->id,
        'bezeichnung' => 'Gerät X',
        'manufacturer' => 'Sony',
    ])->assertRedirect(route('items.index'));

    $item = Item::where('bezeichnung', 'Gerät X')->firstOrFail();
    expect(MonitorDetail::where('item_id', $item->id)->exists())->toBeTrue();

    $this->actingAs($admin)->put(route('items.update', $item), [
        'units_id' => $lensUnit->id,
        'bezeichnung' => 'Gerät X',
        'lens_manufacturer' => 'Canon',
    ])->assertRedirect(route('items.index'));

    expect(MonitorDetail::where('item_id', $item->id)->exists())->toBeFalse()
        ->and(LensDetail::where('item_id', $item->id)->first()?->manufacturer)->toBe('Canon');
});
