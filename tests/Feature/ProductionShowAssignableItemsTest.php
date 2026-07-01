<?php

use App\Models\CameraConfig;
use App\Models\Item;
use App\Models\Production;
use App\Models\Unit;
use App\Models\User;

/**
 * Die "verfügbare Geräte"-Liste wird als JS-Array (`label: "..."`) ins
 * Alpine-Template gerendert, während bereits zugeordnete Geräte an anderer
 * Stelle der Seite in Klartext-HTML auftauchen. Um beide Bereiche nicht zu
 * verwechseln, prüfen wir gezielt auf das `label: "..."`-Muster.
 */
function assignableItemLabels(string $html): array
{
    preg_match_all("/label: '([^']*)'/", $html, $matches);

    return $matches[1];
}

test('show lists items not yet attached and not used in a camera config for this production', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $unit = Unit::create(['bezeichnung' => 'Kameras']);

    $production = Production::create([
        'bezeichnung' => 'Testproduktion',
        'booking_start' => '2026-09-01',
        'booking_end' => '2026-09-05',
    ]);

    $attached = Item::create(['units_id' => $unit->id, 'bezeichnung' => 'Bereits Zugeordnet']);
    $production->items()->attach($attached->id);

    $inConfig = Item::create(['units_id' => $unit->id, 'bezeichnung' => 'In Kamerazug']);
    CameraConfig::create([
        'production_id' => $production->id,
        'item_id' => $inConfig->id,
        'cam_number' => 'Cam 1',
    ]);

    $inConfigAsLens = Item::create(['units_id' => $unit->id, 'bezeichnung' => 'Als Objektiv Im Zug']);
    CameraConfig::create([
        'production_id' => $production->id,
        'item_id' => $attached->id,
        'lens' => $inConfigAsLens->id,
        'cam_number' => 'Cam 2',
    ]);

    $assignable = Item::create(['units_id' => $unit->id, 'bezeichnung' => 'Frei Verfuegbar']);

    $response = $this->actingAs($admin)->get(route('productions.show', $production));
    $response->assertOk();

    $labels = assignableItemLabels($response->getContent());

    expect($labels)->toContain('Frei Verfuegbar')
        ->not->toContain('Bereits Zugeordnet')
        ->not->toContain('In Kamerazug')
        ->not->toContain('Als Objektiv Im Zug');
});

test('show filters assignable items by unit and hides unavailable items unless requested', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $cameraUnit = Unit::create(['bezeichnung' => 'Kameras']);
    $lensUnit = Unit::create(['bezeichnung' => 'Objektive']);

    $production = Production::create([
        'bezeichnung' => 'Testproduktion',
        'booking_start' => '2026-09-01',
        'booking_end' => '2026-09-05',
    ]);

    $camera = Item::create(['units_id' => $cameraUnit->id, 'bezeichnung' => 'Kamera A']);
    $lens = Item::create(['units_id' => $lensUnit->id, 'bezeichnung' => 'Objektiv A']);

    // Objektiv A ist anderweitig gebucht -> nicht verfügbar.
    $conflicting = Production::create([
        'bezeichnung' => 'Konflikt',
        'booking_start' => '2026-09-02',
        'booking_end' => '2026-09-10',
    ]);
    $conflicting->items()->attach($lens->id);

    // Unit-Filter: nur Kameras.
    $response = $this->actingAs($admin)->get(route('productions.show', ['production' => $production->id, 'unit' => $cameraUnit->id]));
    $labels = assignableItemLabels($response->getContent());
    expect($labels)->toContain('Kamera A')->not->toContain('Objektiv A');

    // Ohne show_unavailable: Objektiv A (nicht verfügbar) wird ausgeblendet.
    $response = $this->actingAs($admin)->get(route('productions.show', $production));
    $labels = assignableItemLabels($response->getContent());
    expect($labels)->not->toContain('Objektiv A');

    // Mit show_unavailable=1: Objektiv A wird trotzdem gelistet (als nicht verfügbar markiert).
    $response = $this->actingAs($admin)->get(route('productions.show', ['production' => $production->id, 'show_unavailable' => 1]));
    $labels = assignableItemLabels($response->getContent());
    expect($labels)->toContain('Objektiv A');
});
