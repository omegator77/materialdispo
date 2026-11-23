<?php

use App\Models\CameraConfig;
use App\Models\Geraetetyp;
use App\Models\Item;
use App\Models\Production;
use App\Models\Unit;
use App\Models\User;
use App\Services\CameraSetupService;
use Illuminate\Support\Facades\Route;

// Das Lernen von Kamerazug-Zubehör aus früheren Zügen wurde entfernt: Kamerazüge kommen aus dem
// VB-Protokoll (Vorschlag) oder werden von Hand angelegt.

function lernRig(): array
{
    // Kameras haben die feste Gruppen-ID 1 (unter MySQL setzt der Test-Rollback AUTO_INCREMENT nicht zurück).
    $unit = Unit::forceCreate(['id' => 1, 'bezeichnung' => 'Kameras']);
    $typ = Geraetetyp::create(['units_id' => $unit->id, 'bezeichnung' => 'LDX']);
    $mk = fn (string $n) => Item::create(['units_id' => $unit->id, 'geraetetyp_id' => $typ->id, 'bezeichnung' => $n]);
    $past = Production::create(['bezeichnung' => 'Alt', 'booking_start' => '2026-01-10', 'booking_end' => '2026-01-12']);
    $prod = Production::create(['bezeichnung' => 'Neu', 'booking_start' => '2026-12-10', 'booking_end' => '2026-12-12']);
    $cam = $mk('Kamera 1');
    $lens = $mk('Objektiv 1');
    CameraConfig::create(['production_id' => $past->id, 'item_id' => $cam->id, 'cam_number' => 'Cam 1', 'lens' => $lens->id]);

    return ['user' => User::factory()->create(['role' => 'user']), 'prod' => $prod, 'cam' => $cam, 'lens' => $lens];
}

test('die Route zum automatischen Anlegen von Kamerazügen gibt es nicht mehr', function () {
    expect(Route::has('camera-config.auto'))->toBeFalse();
});

test('die Produktionsseite bietet kein "Kamerazüge automatisch" mehr an', function () {
    $s = lernRig();

    $this->actingAs($s['user'])->get(route('productions.show', $s['prod']))
        ->assertOk()->assertDontSee('Kamerazüge automatisch')->assertDontSee('früheren Zügen');
});

test('das Kamerazug-Formular belegt kein Zubehör aus früheren Zügen vor', function () {
    $s = lernRig();

    $html = $this->actingAs($s['user'])
        ->get(route('camera-config.create', ['production' => $s['prod'], 'camera_item_id' => $s['cam']->id]))
        ->assertOk()->assertDontSee('vorbelegt')->getContent();

    expect($html)->not->toMatch('/<option value="'.$s['lens']->id.'"[^>]*selected/');
});

test('Zug-Nummern zählen nach den vorhandenen weiter', function () {
    $s = lernRig();
    CameraConfig::create(['production_id' => $s['prod']->id, 'item_id' => $s['cam']->id, 'cam_number' => 'Cam 4']);

    expect(app(CameraSetupService::class)->nextCamNumber($s['prod']))->toBe(5);
});
