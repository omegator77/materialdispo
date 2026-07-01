<?php

use App\Models\CameraConfig;
use App\Models\Item;
use App\Models\Production;
use App\Models\Unit;
use App\Models\User;

test('import-from preview shows available items and conflicting items with alternatives', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $unit = Unit::create(['bezeichnung' => 'Kameras']);

    $freeItem = Item::create(['units_id' => $unit->id, 'bezeichnung' => 'Freies Gerät']);
    $bookedItem = Item::create(['units_id' => $unit->id, 'bezeichnung' => 'Gebuchtes Gerät']);
    $alternative = Item::create(['units_id' => $unit->id, 'bezeichnung' => 'Alternative']);

    $source = Production::create([
        'bezeichnung' => 'Vorlage',
        'booking_start' => '2026-05-01',
        'booking_end' => '2026-05-05',
    ]);
    $source->items()->attach([$freeItem->id, $bookedItem->id]);

    $target = Production::create([
        'bezeichnung' => 'Zielproduktion',
        'booking_start' => '2026-05-10',
        'booking_end' => '2026-05-15',
    ]);

    // bookedItem ist während der Zielproduktion anderweitig verplant.
    $conflicting = Production::create([
        'bezeichnung' => 'Konflikt',
        'booking_start' => '2026-05-12',
        'booking_end' => '2026-05-20',
    ]);
    $conflicting->items()->attach($bookedItem->id);

    $response = $this->actingAs($admin)->get(route('productions.importFrom', [$target, $source]));

    $response->assertOk()
        ->assertSee('Freies Gerät')
        ->assertSee('Gebuchtes Gerät')
        ->assertSee('Alternative')
        ->assertSee('Konflikt');
});

test('storeImport keeps available items and replaces conflicting items with the chosen alternative', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $unit = Unit::create(['bezeichnung' => 'Kameras']);

    $freeItem = Item::create(['units_id' => $unit->id, 'bezeichnung' => 'Freies Gerät']);
    $bookedItem = Item::create(['units_id' => $unit->id, 'bezeichnung' => 'Gebuchtes Gerät']);
    $alternative = Item::create(['units_id' => $unit->id, 'bezeichnung' => 'Alternative']);

    $source = Production::create([
        'bezeichnung' => 'Vorlage',
        'booking_start' => '2026-05-01',
        'booking_end' => '2026-05-05',
    ]);
    $source->items()->attach([$freeItem->id, $bookedItem->id]);

    $target = Production::create([
        'bezeichnung' => 'Zielproduktion',
        'booking_start' => '2026-05-10',
        'booking_end' => '2026-05-15',
    ]);

    $conflicting = Production::create([
        'bezeichnung' => 'Konflikt',
        'booking_start' => '2026-05-12',
        'booking_end' => '2026-05-20',
    ]);
    $conflicting->items()->attach($bookedItem->id);

    $response = $this->actingAs($admin)->post(route('productions.storeImport', [$target, $source]), [
        'items' => [
            $freeItem->id => ['action' => 'keep'],
            $bookedItem->id => ['action' => 'replace', 'replacement_id' => $alternative->id],
        ],
    ]);

    $response->assertRedirect(route('productions.show', $target));

    expect($target->items()->where('items.id', $freeItem->id)->exists())->toBeTrue()
        ->and($target->items()->where('items.id', $bookedItem->id)->exists())->toBeFalse()
        ->and($target->items()->where('items.id', $alternative->id)->exists())->toBeTrue();
});

test('storeImport imports selected camera configurations from the source production', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $unit = Unit::create(['bezeichnung' => 'Kameras']);
    $camera = Item::create(['units_id' => $unit->id, 'bezeichnung' => 'Kamera 1']);

    $source = Production::create([
        'bezeichnung' => 'Vorlage',
        'booking_start' => '2026-05-01',
        'booking_end' => '2026-05-05',
    ]);
    $config = CameraConfig::create([
        'production_id' => $source->id,
        'item_id' => $camera->id,
        'cam_number' => 'Cam 1',
    ]);

    $target = Production::create([
        'bezeichnung' => 'Zielproduktion',
        'booking_start' => '2026-05-10',
        'booking_end' => '2026-05-15',
    ]);

    $response = $this->actingAs($admin)->post(route('productions.storeImport', [$target, $source]), [
        'configs' => [
            $config->id => 'import',
        ],
    ]);

    $response->assertRedirect(route('productions.show', $target));

    $imported = CameraConfig::where('production_id', $target->id)->first();
    expect($imported)->not->toBeNull()
        ->and($imported->cam_number)->toBe('Cam 1')
        ->and((int) $imported->item_id)->toBe($camera->id);
});
