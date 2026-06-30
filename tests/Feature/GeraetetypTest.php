<?php

use App\Models\Geraetetyp;
use App\Models\Item;
use App\Models\Production;
use App\Models\Unit;
use App\Models\User;

test('admin can create, list and delete a geraetetyp', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $unit = Unit::create(['bezeichnung' => 'Kameras']);

    $this->actingAs($admin)->post(route('geraetetypen.store'), [
        'units_id' => $unit->id,
        'bezeichnung' => 'LDX 86N WorldCam',
    ])->assertRedirect(route('geraetetypen.index'));

    $geraetetyp = Geraetetyp::where('bezeichnung', 'LDX 86N WorldCam')->firstOrFail();

    $this->actingAs($admin)->get(route('geraetetypen.index'))
        ->assertOk()
        ->assertSee('LDX 86N WorldCam');

    $this->actingAs($admin)->delete(route('geraetetypen.destroy', $geraetetyp->id))
        ->assertRedirect(route('geraetetypen.index'));

    expect(Geraetetyp::find($geraetetyp->id))->toBeNull();
});

test('deleting a geraetetyp that is still referenced by items is blocked', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $unit = Unit::create(['bezeichnung' => 'Kameras']);
    $geraetetyp = Geraetetyp::create(['units_id' => $unit->id, 'bezeichnung' => 'LDX 86N WorldCam']);
    Item::create(['units_id' => $unit->id, 'geraetetyp_id' => $geraetetyp->id, 'bezeichnung' => 'LDX 86N WorldCam', 'is_rented' => false]);

    $response = $this->actingAs($admin)->delete(route('geraetetypen.destroy', $geraetetyp->id));

    $response->assertRedirect(route('geraetetypen.index'));
    $response->assertSessionHas('error');
    expect(Geraetetyp::find($geraetetyp->id))->not->toBeNull();
});

test('item can be created with a geraetetyp that auto-fills bezeichnung', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $unit = Unit::create(['bezeichnung' => 'Kameras']);
    $geraetetyp = Geraetetyp::create(['units_id' => $unit->id, 'bezeichnung' => 'LDX 86N WorldCam']);

    $this->actingAs($admin)->post(route('items.store'), [
        'units_id' => $unit->id,
        'geraetetyp_id' => $geraetetyp->id,
        'bezeichnung' => 'LDX 86N WorldCam',
        'nummer' => '99',
    ])->assertRedirect(route('items.index'));

    $item = Item::where('nummer', '99')->firstOrFail();
    expect($item->geraetetyp_id)->toBe($geraetetyp->id);
});

test('vb-protokoll anforderung can target a specific geraetetyp and abgleich counts only matching items', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $unit = Unit::create(['bezeichnung' => 'Kameras']);
    $typeA = Geraetetyp::create(['units_id' => $unit->id, 'bezeichnung' => 'LDX 86N WorldCam']);
    $typeB = Geraetetyp::create(['units_id' => $unit->id, 'bezeichnung' => 'LDX 86N Universe']);

    $production = Production::create([
        'bezeichnung' => 'VB Typ-Test',
        'booking_start' => '2026-10-01',
        'booking_end' => '2026-10-03',
    ]);

    $this->actingAs($admin)->post(route('vb-protokoll.store', $production->id), [
        'kunde' => 'Testkunde',
        'anforderungen' => [
            ['mode' => 'typ', 'unit_id' => $unit->id, 'geraetetyp_id' => $typeA->id, 'anzahl' => 2],
        ],
    ])->assertRedirect(route('vb-protokoll.show', $production->id));

    // 1x passender Typ A + 1x Typ B (gleiche Gruppe, anderer Typ) packen
    $itemA = Item::create(['units_id' => $unit->id, 'geraetetyp_id' => $typeA->id, 'bezeichnung' => 'LDX 86N WorldCam', 'is_rented' => false]);
    $itemB = Item::create(['units_id' => $unit->id, 'geraetetyp_id' => $typeB->id, 'bezeichnung' => 'LDX 86N Universe', 'is_rented' => false]);
    $production->items()->attach([$itemA->id, $itemB->id]);

    $vbProtokoll = $production->fresh()->vbProtokoll;
    $abgleich = $vbProtokoll->abgleich();

    expect($abgleich)->toHaveCount(1)
        ->and($abgleich->first()['label'])->toBe('LDX 86N WorldCam')
        ->and($abgleich->first()['kind'])->toBe('typ')
        ->and($abgleich->first()['gepackt'])->toBe(1)
        ->and($abgleich->first()['erfuellt'])->toBeFalse();

    $response = $this->actingAs($admin)->get(route('vb-protokoll.show', $production->id));
    $response->assertOk()->assertSee('LDX 86N WorldCam')->assertSee('Typ');
});
