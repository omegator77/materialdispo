<?php

use App\Models\Item;
use App\Models\Mietvorgang;
use App\Models\Production;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'user']);
    $this->unit = Unit::create(['bezeichnung' => 'Kameras']);
    $this->items = collect(range(1, 3))->map(fn ($i) => Item::create(['units_id' => $this->unit->id, 'bezeichnung' => "Cam {$i}", 'nummer' => (string) $i]));
});

test('several devices can be assigned to a production at once', function () {
    $prod = Production::create(['bezeichnung' => 'P', 'booking_start' => '2026-12-01', 'booking_end' => '2026-12-03']);

    $this->actingAs($this->user)->post(route('items.bulk.action'), [
        'action' => 'production', 'target_id' => $prod->id, 'item_ids' => $this->items->pluck('id')->all(),
    ])->assertSessionHas('success');

    expect($prod->items()->count())->toBe(3);
});

test('bulk assign reports devices that conflict instead of failing entirely', function () {
    $prod = Production::create(['bezeichnung' => 'P', 'booking_start' => '2026-12-01', 'booking_end' => '2026-12-03']);
    $other = Production::create(['bezeichnung' => 'Q', 'booking_start' => '2026-12-02', 'booking_end' => '2026-12-04']);
    $other->items()->attach($this->items[0]->id);

    $this->actingAs($this->user)->post(route('items.bulk.action'), [
        'action' => 'production', 'target_id' => $prod->id, 'item_ids' => $this->items->pluck('id')->all(),
    ])->assertSessionHas('warning');

    expect($prod->items()->count())->toBe(2);
});

test('devices can be moved to another group in bulk', function () {
    $target = Unit::create(['bezeichnung' => 'Reserve']);

    $this->actingAs($this->user)->post(route('items.bulk.action'), [
        'action' => 'unit', 'target_id' => $target->id, 'item_ids' => $this->items->pluck('id')->all(),
    ])->assertSessionHas('success');

    expect(Item::where('units_id', $target->id)->count())->toBe(3);
});

test('devices can be assigned to a Mietvorgang in bulk', function () {
    $supplier = Supplier::create(['bezeichnung' => 'Pixmade']);
    $mv = Mietvorgang::create(['suppliers_id' => $supplier->id, 'rent_start' => '2026-12-01', 'rent_end' => '2026-12-10', 'bezeichnung' => 'M']);

    $this->actingAs($this->user)->post(route('items.bulk.action'), [
        'action' => 'mietvorgang', 'target_id' => $mv->id, 'item_ids' => $this->items->pluck('id')->all(),
    ])->assertSessionHas('success');

    expect($mv->items()->count())->toBe(3);
});

test('bulk action validates input and rejects viewers', function () {
    $this->actingAs($this->user)->post(route('items.bulk.action'), ['action' => 'boom', 'item_ids' => [], 'target_id' => 1])
        ->assertSessionHasErrors(['action', 'item_ids']);

    $viewer = User::factory()->create(['role' => 'viewer']);
    $this->actingAs($viewer)->post(route('items.bulk.action'), [])->assertForbidden();
});
