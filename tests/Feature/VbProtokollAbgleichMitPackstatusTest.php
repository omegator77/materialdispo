<?php

use App\Models\CameraConfig;
use App\Models\Geraetetyp;
use App\Models\Item;
use App\Models\Production;
use App\Models\ProductionItemPack;
use App\Models\Unit;
use App\Models\User;
use App\Models\VbProtokoll;

test('typ-anforderung is only fulfilled once the matching item is physically packed', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $unit = Unit::create(['bezeichnung' => 'Kameras']);
    $typ = Geraetetyp::create(['units_id' => $unit->id, 'bezeichnung' => 'LDX 86N']);

    $production = Production::create([
        'bezeichnung' => 'Testproduktion',
        'booking_start' => '2026-11-01',
        'booking_end' => '2026-11-05',
    ]);

    $vbProtokoll = VbProtokoll::create(['production_id' => $production->id, 'created_by' => $admin->id]);
    $vbProtokoll->anforderungen()->create(['unit_id' => $unit->id, 'geraetetyp_id' => $typ->id, 'anzahl' => 1]);

    $item = Item::create(['units_id' => $unit->id, 'geraetetyp_id' => $typ->id, 'bezeichnung' => 'Kamera 1']);
    $production->items()->attach($item->id);

    // Zugeordnet, aber noch nicht physisch gepackt.
    $row = $vbProtokoll->fresh()->abgleichMitPackstatus()->firstWhere('label', 'LDX 86N');
    expect($row['benoetigt'])->toBe(1)
        ->and($row['zugeordnet'])->toBe(1)
        ->and($row['gepackt'])->toBe(0)
        ->and($row['erfuellt'])->toBeFalse();

    // Jetzt im Packvorgang abgehakt.
    ProductionItemPack::create([
        'production_id' => $production->id,
        'item_id' => $item->id,
        'packed_by' => $admin->id,
        'packed_at' => now(),
    ]);

    $row = $vbProtokoll->fresh()->abgleichMitPackstatus()->firstWhere('label', 'LDX 86N');
    expect($row['zugeordnet'])->toBe(1)
        ->and($row['gepackt'])->toBe(1)
        ->and($row['erfuellt'])->toBeTrue();
});

test('kamera-anforderung components each track packstatus independently', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $kameraUnit = Unit::create(['bezeichnung' => 'Kameras']);
    $optikUnit = Unit::create(['bezeichnung' => 'Optiken']);

    $kameraTyp = Geraetetyp::create(['units_id' => $kameraUnit->id, 'bezeichnung' => 'LDX 86N']);
    $optikTyp = Geraetetyp::create(['units_id' => $optikUnit->id, 'bezeichnung' => 'Fujinon HA23']);

    $production = Production::create([
        'bezeichnung' => 'Testproduktion',
        'booking_start' => '2026-11-01',
        'booking_end' => '2026-11-05',
    ]);

    $vbProtokoll = VbProtokoll::create(['production_id' => $production->id, 'created_by' => $admin->id]);
    $vbProtokoll->anforderungen()->create([
        'cam_number' => 'Kamera 1',
        'geraetetyp_id' => $kameraTyp->id,
        'unit_id' => $kameraUnit->id,
        'lens_geraetetyp_id' => $optikTyp->id,
    ]);

    $kamera = Item::create(['units_id' => $kameraUnit->id, 'geraetetyp_id' => $kameraTyp->id, 'bezeichnung' => 'Kamera 1']);
    $optik = Item::create(['units_id' => $optikUnit->id, 'geraetetyp_id' => $optikTyp->id, 'bezeichnung' => 'Optik 1']);

    CameraConfig::create([
        'production_id' => $production->id,
        'item_id' => $kamera->id,
        'lens' => $optik->id,
        'cam_number' => 'Kamera 1',
    ]);

    // Nur die Kamera wird physisch gepackt, die Optik nicht.
    ProductionItemPack::create([
        'production_id' => $production->id,
        'item_id' => $kamera->id,
        'packed_by' => $admin->id,
        'packed_at' => now(),
    ]);

    $abgleich = $vbProtokoll->fresh()->abgleichMitPackstatus();

    $kameraRow = $abgleich->firstWhere('label', 'Kamera Kamera 1 – Kamera: LDX 86N');
    expect($kameraRow['zugeordnet'])->toBe(1)
        ->and($kameraRow['gepackt'])->toBe(1)
        ->and($kameraRow['erfuellt'])->toBeTrue();

    $optikRow = $abgleich->firstWhere('label', 'Kamera Kamera 1 – Objektiv: Fujinon HA23');
    expect($optikRow['zugeordnet'])->toBe(1)
        ->and($optikRow['gepackt'])->toBe(0)
        ->and($optikRow['erfuellt'])->toBeFalse();
});

test('freitext-anforderung has no packstatus and must be checked manually', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $production = Production::create([
        'bezeichnung' => 'Testproduktion',
        'booking_start' => '2026-11-01',
        'booking_end' => '2026-11-05',
    ]);

    $vbProtokoll = VbProtokoll::create(['production_id' => $production->id, 'created_by' => $admin->id]);
    $vbProtokoll->anforderungen()->create(['freitext' => 'Sandsäcke', 'anzahl' => 5]);

    $row = $vbProtokoll->fresh()->abgleichMitPackstatus()->firstWhere('kind', 'frei');

    expect($row['zugeordnet'])->toBeNull()
        ->and($row['gepackt'])->toBeNull()
        ->and($row['erfuellt'])->toBeNull();
});
