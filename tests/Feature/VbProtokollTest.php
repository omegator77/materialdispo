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

test('admin can create a vb-protokoll with anforderungen', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $production = makeVbProduction();
    $unit = Unit::create(['bezeichnung' => 'Kameras']);

    $response = $this->actingAs($admin)->post(route('vb-protokoll.store', $production->id), [
        'kunde' => 'Testkunde',
        'produktionsort' => 'Teststadt',
        'crew_ul' => '1',
        'anforderungen' => [
            ['mode' => 'typ', 'unit_id' => $unit->id, 'anzahl' => 3],
        ],
    ]);

    $response->assertRedirect(route('vb-protokoll.show', $production->id));

    $vbProtokoll = VbProtokoll::where('production_id', $production->id)->firstOrFail();
    expect($vbProtokoll->kunde)->toBe('Testkunde')
        ->and($vbProtokoll->anforderungen)->toHaveCount(1);
});

test('admin can create a vb-protokoll requirement targeting a specific geraetetyp', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $production = makeVbProduction();
    $unit = Unit::create(['bezeichnung' => 'Stative']);
    $eng = \App\Models\Geraetetyp::create(['units_id' => $unit->id, 'bezeichnung' => 'ENG']);
    $quattro = \App\Models\Geraetetyp::create(['units_id' => $unit->id, 'bezeichnung' => 'Quattro']);

    $response = $this->actingAs($admin)->post(route('vb-protokoll.store', $production->id), [
        'kunde' => 'Testkunde',
        'anforderungen' => [
            ['mode' => 'typ', 'unit_id' => $unit->id, 'geraetetyp_id' => $eng->id, 'anzahl' => 2],
            ['mode' => 'typ', 'unit_id' => $unit->id, 'geraetetyp_id' => $quattro->id, 'anzahl' => 1],
        ],
    ]);

    $response->assertRedirect(route('vb-protokoll.show', $production->id));

    $vbProtokoll = VbProtokoll::where('production_id', $production->id)->firstOrFail();
    expect($vbProtokoll->anforderungen)->toHaveCount(2);

    $engAnforderung = $vbProtokoll->anforderungen->firstWhere('geraetetyp_id', $eng->id);
    expect($engAnforderung->unit_id)->toBe($unit->id)
        ->and($engAnforderung->anzahl)->toBe(2);

    $item1 = \App\Models\Item::create(['units_id' => $unit->id, 'geraetetyp_id' => $eng->id, 'bezeichnung' => 'ENG 1', 'is_rented' => false]);
    $item2 = \App\Models\Item::create(['units_id' => $unit->id, 'geraetetyp_id' => $quattro->id, 'bezeichnung' => 'Quattro 1', 'is_rented' => false]);
    $production->items()->attach([$item1->id, $item2->id]);

    $abgleich = $vbProtokoll->fresh()->abgleich();
    $engRow = $abgleich->firstWhere('label', 'ENG');
    expect($engRow['gepackt'])->toBe(1)
        ->and($engRow['erfuellt'])->toBeFalse();
});

test('admin can create arbitrary freitext-bloecke with a custom heading', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $production = makeVbProduction();

    $response = $this->actingAs($admin)->post(route('vb-protokoll.store', $production->id), [
        'kunde' => 'Testkunde',
        'freitext_bloecke' => [
            ['ueberschrift' => 'Audio', 'text' => '5 Mikrofone, 3 In Ears'],
            ['ueberschrift' => 'Kabelweg', 'text' => 'blablub bla'],
            ['ueberschrift' => '', 'text' => ''],
        ],
    ]);

    $response->assertRedirect(route('vb-protokoll.show', $production->id));

    $vbProtokoll = VbProtokoll::where('production_id', $production->id)->firstOrFail();
    expect($vbProtokoll->freitextBloecke)->toHaveCount(2);

    $audioBlock = $vbProtokoll->freitextBloecke->firstWhere('ueberschrift', 'Audio');
    expect($audioBlock->text)->toBe('5 Mikrofone, 3 In Ears');

    $this->actingAs($admin)->get(route('vb-protokoll.show', $production->id))
        ->assertOk()->assertSee('Audio')->assertSee('Kabelweg')->assertSee('blablub bla');
});

test('admin can define a typ-based kamerakonfiguration as an anforderung', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $production = makeVbProduction();
    $kameraUnit = Unit::create(['bezeichnung' => 'Kameras']);
    $optikUnit = Unit::create(['bezeichnung' => 'Optiken']);
    $stativUnit = Unit::create(['bezeichnung' => 'Stative']);

    $kameraTyp = \App\Models\Geraetetyp::create(['units_id' => $kameraUnit->id, 'bezeichnung' => 'LDX 86N WorldCam']);
    $optikTyp = \App\Models\Geraetetyp::create(['units_id' => $optikUnit->id, 'bezeichnung' => 'Fujinon HA23']);
    $stativTyp = \App\Models\Geraetetyp::create(['units_id' => $stativUnit->id, 'bezeichnung' => 'Sachtler Quattro']);

    $response = $this->actingAs($admin)->post(route('vb-protokoll.store', $production->id), [
        'kunde' => 'Testkunde',
        'anforderungen' => [
            [
                'mode' => 'kamera',
                'cam_number' => 'Kamera 1',
                'geraetetyp_id' => $kameraTyp->id,
                'lens_geraetetyp_id' => $optikTyp->id,
                'tripod_geraetetyp_id' => $stativTyp->id,
                'notiz' => 'Position Mitte',
            ],
        ],
    ]);

    $response->assertRedirect(route('vb-protokoll.show', $production->id));

    $vbProtokoll = VbProtokoll::where('production_id', $production->id)->firstOrFail();
    expect($vbProtokoll->anforderungen)->toHaveCount(1);

    $anforderung = $vbProtokoll->anforderungen->first();
    expect($anforderung->cam_number)->toBe('Kamera 1')
        ->and($anforderung->geraetetyp_id)->toBe($kameraTyp->id)
        ->and($anforderung->unit_id)->toBe($kameraUnit->id)
        ->and($anforderung->lens_geraetetyp_id)->toBe($optikTyp->id)
        ->and($anforderung->tripod_geraetetyp_id)->toBe($stativTyp->id)
        ->and($anforderung->tripod_head_geraetetyp_id)->toBeNull();

    $item = \App\Models\Item::create(['units_id' => $kameraUnit->id, 'geraetetyp_id' => $kameraTyp->id, 'bezeichnung' => 'LDX 86N WorldCam', 'is_rented' => false]);
    $production->items()->attach($item->id);

    $abgleich = $vbProtokoll->fresh()->abgleich();
    expect($abgleich)->toHaveCount(3)
        ->and($abgleich->every(fn ($row) => $row['kind'] === 'kamera'))->toBeTrue();

    $kameraRow = $abgleich->firstWhere('label', 'Kamera Kamera 1 – Kamera: LDX 86N WorldCam');
    expect($kameraRow['benoetigt'])->toBe(1)
        ->and($kameraRow['gepackt'])->toBe(1)
        ->and($kameraRow['erfuellt'])->toBeTrue()
        ->and($kameraRow['notiz'])->toBe('Position Mitte');

    $optikRow = $abgleich->firstWhere('label', 'Kamera Kamera 1 – Objektiv: Fujinon HA23');
    expect($optikRow['gepackt'])->toBe(0)
        ->and($optikRow['erfuellt'])->toBeFalse();

    $response = $this->actingAs($admin)->get(route('vb-protokoll.show', $production->id));
    $response->assertOk()->assertSee('Kamerakonfig')->assertSee('LDX 86N WorldCam');
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

test('assignment status is grau (not gruen) when the vb-protokoll has no anforderungen yet', function () {
    $production = makeVbProduction();
    $production->vbProtokoll()->create(['kunde' => 'X']);

    $status = $production->fresh()->assignmentStatus();

    expect($status['level'])->toBe('grau')
        ->and($status['label'])->toBe('Keine Anforderungen im VB-Protokoll');
});

test('deleting a vb-protokoll removes its anforderungen', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $production = makeVbProduction();
    $unit = Unit::create(['bezeichnung' => 'Kameras']);

    $vbProtokoll = $production->vbProtokoll()->create(['kunde' => 'X']);
    $vbProtokoll->anforderungen()->create(['unit_id' => $unit->id, 'anzahl' => 1]);

    $this->actingAs($admin)->delete(route('vb-protokoll.destroy', $production->id))
        ->assertRedirect(route('productions.show', $production->id));

    expect(VbProtokoll::find($vbProtokoll->id))->toBeNull();
    expect(\App\Models\VbProtokollAnforderung::count())->toBe(0);
});
