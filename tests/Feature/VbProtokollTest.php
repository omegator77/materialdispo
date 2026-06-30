<?php

use App\Models\Production;
use App\Models\Unit;
use App\Models\User;
use App\Models\VbProtokoll;

function makeVbProduction(): Production
{
    return Production::create([
        'bezeichnung' => 'VB Testproduktion',
        'booking_start' => '2026-09-01',
        'booking_end' => '2026-09-03',
    ]);
}

test('admin can create a vb-protokoll with kameras and anforderungen', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $production = makeVbProduction();
    $unit = Unit::create(['bezeichnung' => 'Kameras']);

    $response = $this->actingAs($admin)->post(route('vb-protokoll.store', $production->id), [
        'kunde' => 'Testkunde',
        'produktionsort' => 'Teststadt',
        'crew_ul' => '1',
        'kameras' => [
            ['position' => 1, 'bezeichnung' => '22x ENG'],
        ],
        'anforderungen' => [
            ['unit_id' => $unit->id, 'anzahl' => 3],
        ],
    ]);

    $response->assertRedirect(route('vb-protokoll.show', $production->id));

    $vbProtokoll = VbProtokoll::where('production_id', $production->id)->firstOrFail();
    expect($vbProtokoll->kunde)->toBe('Testkunde')
        ->and($vbProtokoll->kameras)->toHaveCount(1)
        ->and($vbProtokoll->anforderungen)->toHaveCount(1);
});

test('vb-protokoll show page renders the soll/ist abgleich against the packlist', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $production = makeVbProduction();
    $unit = Unit::create(['bezeichnung' => 'Kameras']);

    $vbProtokoll = $production->vbProtokoll()->create(['kunde' => 'X', 'created_by' => $admin->id]);
    $vbProtokoll->anforderungen()->create(['unit_id' => $unit->id, 'anzahl' => 2]);

    $item = \App\Models\Item::create(['units_id' => $unit->id, 'bezeichnung' => 'Kamera 1', 'is_rented' => false]);
    $production->items()->attach($item->id);

    $response = $this->actingAs($admin)->get(route('vb-protokoll.show', $production->id));

    $response->assertOk();
    $response->assertSee('Kameras');
    $response->assertSee('fehlt 1');
});

test('viewer cannot create or edit a vb-protokoll but can view it', function () {
    $viewer = User::factory()->create(['role' => 'viewer']);
    $production = makeVbProduction();

    $this->actingAs($viewer)->get(route('vb-protokoll.create', $production->id))->assertForbidden();

    $production->vbProtokoll()->create(['kunde' => 'X']);

    $this->actingAs($viewer)->get(route('vb-protokoll.show', $production->id))->assertOk();
    $this->actingAs($viewer)->get(route('vb-protokoll.edit', $production->id))->assertForbidden();
});

test('deleting a vb-protokoll removes its kameras and anforderungen', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $production = makeVbProduction();
    $unit = Unit::create(['bezeichnung' => 'Kameras']);

    $vbProtokoll = $production->vbProtokoll()->create(['kunde' => 'X']);
    $vbProtokoll->kameras()->create(['position' => 1, 'bezeichnung' => 'Test']);
    $vbProtokoll->anforderungen()->create(['unit_id' => $unit->id, 'anzahl' => 1]);

    $this->actingAs($admin)->delete(route('vb-protokoll.destroy', $production->id))
        ->assertRedirect(route('productions.show', $production->id));

    expect(VbProtokoll::find($vbProtokoll->id))->toBeNull();
    expect(\App\Models\VbProtokollKamera::count())->toBe(0);
    expect(\App\Models\VbProtokollAnforderung::count())->toBe(0);
});
