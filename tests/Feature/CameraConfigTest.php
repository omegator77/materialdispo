<?php

use App\Models\CameraConfig;
use App\Models\Item;
use App\Models\Production;
use App\Models\Unit;
use App\Models\User;

test('admin can create, edit, update and delete a camera configuration', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $unit = Unit::create(['bezeichnung' => 'Kameras']);
    $camera = Item::create(['units_id' => $unit->id, 'bezeichnung' => 'Kamera 1']);
    $lens = Item::create(['units_id' => $unit->id, 'bezeichnung' => 'Objektiv 1']);

    $production = Production::create([
        'bezeichnung' => 'Testproduktion',
        'booking_start' => '2026-08-01',
        'booking_end' => '2026-08-05',
    ]);

    $this->actingAs($admin)->get(route('camera-config.create', $production))->assertOk();

    $this->actingAs($admin)->post(route('camera-config.store', $production), [
        'cam_number' => 'Cam 1',
        'camera' => $camera->id,
        'lens' => $lens->id,
    ])->assertRedirect(route('productions.show', $production));

    $config = CameraConfig::where('production_id', $production->id)->firstOrFail();
    expect((int) $config->item_id)->toBe($camera->id)
        ->and((int) $config->lens)->toBe($lens->id);

    $this->actingAs($admin)->get(route('camera-config.edit', $config))->assertOk();

    $this->actingAs($admin)->put(route('camera-config.update', $config), [
        'cam_number' => 'Cam 1 aktualisiert',
        'camera' => $camera->id,
        'lens' => $lens->id,
    ])->assertRedirect(route('productions.show', $config->production_id));

    expect($config->fresh()->cam_number)->toBe('Cam 1 aktualisiert');

    $this->actingAs($admin)->delete(route('camera-config.destroy', $config));

    expect(CameraConfig::find($config->id))->toBeNull();
});

test('updating a camera config does not conflict with its own existing item assignments', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $unit = Unit::create(['bezeichnung' => 'Kameras']);
    $camera = Item::create(['units_id' => $unit->id, 'bezeichnung' => 'Kamera 1']);
    $lens = Item::create(['units_id' => $unit->id, 'bezeichnung' => 'Objektiv 1']);

    $production = Production::create([
        'bezeichnung' => 'Testproduktion',
        'booking_start' => '2026-08-01',
        'booking_end' => '2026-08-05',
    ]);

    $config = CameraConfig::create([
        'production_id' => $production->id,
        'item_id' => $camera->id,
        'lens' => $lens->id,
        'cam_number' => 'Cam 1',
    ]);

    // Re-saving the same config with the same items must not be rejected as
    // "already used in this camera configuration" (self-conflict).
    $response = $this->actingAs($admin)->put(route('camera-config.update', $config), [
        'cam_number' => 'Cam 1',
        'camera' => $camera->id,
        'lens' => $lens->id,
        'notes' => 'Aktualisierte Notiz',
    ]);

    $response->assertRedirect(route('productions.show', $production->id));
    $response->assertSessionMissing('error');
    expect($config->fresh()->notes)->toBe('Aktualisierte Notiz');
});

test('camera config item selection is rejected when the item is booked in an overlapping production', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $unit = Unit::create(['bezeichnung' => 'Kameras']);
    $camera = Item::create(['units_id' => $unit->id, 'bezeichnung' => 'Kamera 1']);

    $otherProduction = Production::create([
        'bezeichnung' => 'Andere Produktion',
        'booking_start' => '2026-08-01',
        'booking_end' => '2026-08-10',
    ]);
    $otherProduction->items()->attach($camera->id);

    $production = Production::create([
        'bezeichnung' => 'Testproduktion',
        'booking_start' => '2026-08-05',
        'booking_end' => '2026-08-15',
    ]);

    $response = $this->actingAs($admin)->post(route('camera-config.store', $production), [
        'cam_number' => 'Cam 1',
        'camera' => $camera->id,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');
    expect(CameraConfig::where('production_id', $production->id)->exists())->toBeFalse();
});
