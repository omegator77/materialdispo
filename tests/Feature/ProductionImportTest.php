<?php

use App\Models\CameraConfig;
use App\Models\Item;
use App\Models\Production;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\Event;

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

/*
|--------------------------------------------------------------------------
| storeImport: Validierung und Verfügbarkeitsprüfung beim Übernehmen
|--------------------------------------------------------------------------
*/

function importScenario(): array
{
    $admin = User::factory()->create(['role' => 'admin']);
    $unit = Unit::create(['bezeichnung' => 'Kameras']);

    $source = Production::create(['bezeichnung' => 'Vorlage', 'booking_start' => '2026-05-01', 'booking_end' => '2026-05-05']);
    $target = Production::create(['bezeichnung' => 'Zielproduktion', 'booking_start' => '2026-05-10', 'booking_end' => '2026-05-15']);

    return [$admin, $unit, $source, $target];
}

test('storeImport lehnt ungültige Aktionen und unbekannte Ersatzgeräte per Validierung ab', function () {
    [$admin, $unit, $source, $target] = importScenario();
    $item = Item::create(['units_id' => $unit->id, 'bezeichnung' => 'Gerät']);
    $source->items()->attach($item->id);

    $this->actingAs($admin)
        ->post(route('productions.storeImport', [$target, $source]), [
            'items' => [$item->id => ['action' => 'löschen']],
        ])->assertSessionHasErrors('items.'.$item->id.'.action');

    $this->actingAs($admin)
        ->post(route('productions.storeImport', [$target, $source]), [
            'items' => [$item->id => ['action' => 'replace', 'replacement_id' => 999999]],
        ])->assertSessionHasErrors('items.'.$item->id.'.replacement_id');

    expect($target->items()->count())->toBe(0);
});

test('storeImport übernimmt keine Geräte, die nicht in der Vorlage enthalten sind', function () {
    [$admin, $unit, $source, $target] = importScenario();
    $fremd = Item::create(['units_id' => $unit->id, 'bezeichnung' => 'Nicht in Vorlage']);

    $response = $this->actingAs($admin)->post(route('productions.storeImport', [$target, $source]), [
        'items' => [$fremd->id => ['action' => 'keep']],
    ]);

    $response->assertRedirect(route('productions.show', $target));
    expect($target->items()->count())->toBe(0);
});

test('storeImport übernimmt kein Gerät mit Verfügbarkeitskonflikt per keep und meldet es', function () {
    [$admin, $unit, $source, $target] = importScenario();
    $item = Item::create(['units_id' => $unit->id, 'bezeichnung' => 'Gebuchtes Gerät']);
    $source->items()->attach($item->id);

    $konflikt = Production::create(['bezeichnung' => 'Konflikt', 'booking_start' => '2026-05-12', 'booking_end' => '2026-05-20']);
    $konflikt->items()->attach($item->id);

    $response = $this->actingAs($admin)->post(route('productions.storeImport', [$target, $source]), [
        'items' => [$item->id => ['action' => 'keep']],
    ]);

    $response->assertRedirect(route('productions.show', $target))->assertSessionHas('warning');
    expect($target->items()->count())->toBe(0)
        ->and(session('warning'))->toContain('Gebuchtes Gerät')->toContain('Konflikt');
});

test('storeImport übernimmt keinen Kamerazug mit nicht verfügbarem Slot', function () {
    [$admin, $unit, $source, $target] = importScenario();
    $kamera = Item::create(['units_id' => $unit->id, 'bezeichnung' => 'Kamera']);
    $objektiv = Item::create(['units_id' => $unit->id, 'bezeichnung' => 'Objektiv']);

    $config = CameraConfig::create([
        'production_id' => $source->id, 'item_id' => $kamera->id, 'lens' => (string) $objektiv->id, 'cam_number' => 'Cam 1',
    ]);

    $konflikt = Production::create(['bezeichnung' => 'Konflikt', 'booking_start' => '2026-05-12', 'booking_end' => '2026-05-20']);
    $konflikt->items()->attach($objektiv->id);

    $this->actingAs($admin)->post(route('productions.storeImport', [$target, $source]), [
        'configs' => [$config->id => 'import'],
    ])->assertSessionHas('warning');

    expect(CameraConfig::where('production_id', $target->id)->count())->toBe(0);
});

test('storeImport macht bei einem Fehler mittendrin nichts halb', function () {
    [$admin, $unit, $source, $target] = importScenario();
    $ok = Item::create(['units_id' => $unit->id, 'bezeichnung' => 'Frei']);
    $source->items()->attach($ok->id);
    $config = CameraConfig::create(['production_id' => $source->id, 'item_id' => $ok->id, 'cam_number' => 'Cam 1']);

    // Der Kamerazug-Import schlägt NACH dem Geräte-Import fehl.
    $event = 'eloquent.creating: '.CameraConfig::class;
    Event::listen($event, fn () => throw new RuntimeException('Simulierter Fehler'));

    try {
        $this->withoutExceptionHandling()->actingAs($admin)->post(route('productions.storeImport', [$target, $source]), [
            'items' => [$ok->id => ['action' => 'keep']],
            'configs' => [$config->id => 'import'],
        ]);
    } catch (RuntimeException) {
    } finally {
        Event::forget($event);
    }

    expect($target->items()->count())->toBe(0);
});
