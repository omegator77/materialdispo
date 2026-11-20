<?php

use App\Models\CameraConfig;
use App\Models\Item;
use App\Models\Production;
use App\Models\Unit;
use App\Models\User;

function rigSetup(): array
{
    $unit = Unit::create(['bezeichnung' => 'Kameras']);
    $production = Production::create([
        'bezeichnung' => 'Testproduktion', 'booking_start' => '2026-08-01', 'booking_end' => '2026-08-05',
    ]);
    $mk = fn (string $n) => Item::create(['units_id' => $unit->id, 'bezeichnung' => $n]);

    return [
        'admin' => User::factory()->create(['role' => 'admin']),
        'production' => $production,
        'camA' => $mk('Kamera A'), 'camB' => $mk('Kamera B'),
        'tripod' => $mk('Stativ 1'), 'lens' => $mk('Objektiv 1'),
    ];
}

test('dasselbe Stativ in zwei Kamerazügen derselben Produktion wird abgelehnt', function () {
    $s = rigSetup();
    $this->actingAs($s['admin'])->post(route('camera-config.store', $s['production']), [
        'cam_number' => 'Cam 1', 'camera' => $s['camA']->id, 'tripod' => $s['tripod']->id,
    ])->assertSessionHasNoErrors();

    $this->actingAs($s['admin'])->post(route('camera-config.store', $s['production']), [
        'cam_number' => 'Cam 2', 'camera' => $s['camB']->id, 'tripod' => $s['tripod']->id,
    ])->assertSessionHas('error');

    expect(CameraConfig::where('production_id', $s['production']->id)->count())->toBe(1);
});

test('die Fehlermeldung nennt Gerät und den Kamerazug, der es schon hat', function () {
    $s = rigSetup();
    CameraConfig::create(['production_id' => $s['production']->id, 'item_id' => $s['camA']->id, 'cam_number' => 'Cam 1', 'tripod' => $s['tripod']->id]);

    $this->actingAs($s['admin'])->post(route('camera-config.store', $s['production']), [
        'cam_number' => 'Cam 2', 'camera' => $s['camB']->id, 'tripod' => $s['tripod']->id,
    ])->assertSessionHas('error', fn ($m) => str_contains($m, 'Stativ 1') && str_contains($m, 'Cam 1'));
});

test('Bearbeiten auf ein Gerät eines anderen Kamerazugs wird abgelehnt', function () {
    $s = rigSetup();
    CameraConfig::create(['production_id' => $s['production']->id, 'item_id' => $s['camA']->id, 'cam_number' => 'Cam 1', 'tripod' => $s['tripod']->id]);
    $b = CameraConfig::create(['production_id' => $s['production']->id, 'item_id' => $s['camB']->id, 'cam_number' => 'Cam 2']);

    $this->actingAs($s['admin'])->put(route('camera-config.update', $b), [
        'cam_number' => 'Cam 2', 'camera' => $s['camB']->id, 'tripod' => $s['tripod']->id,
    ])->assertSessionHas('error');

    expect($b->fresh()->tripod)->toBeNull();
});

test('ein Gerät in einem anderen Slot eines anderen Kamerazugs zählt ebenfalls', function () {
    $s = rigSetup();
    CameraConfig::create(['production_id' => $s['production']->id, 'item_id' => $s['camA']->id, 'cam_number' => 'Cam 1', 'lens' => $s['lens']->id]);

    $this->actingAs($s['admin'])->post(route('camera-config.store', $s['production']), [
        'cam_number' => 'Cam 2', 'camera' => $s['camB']->id, 'lens' => $s['lens']->id,
    ])->assertSessionHas('error');
});

test('der eigene Kamerazug blockiert sich beim Speichern nicht selbst', function () {
    $s = rigSetup();
    $a = CameraConfig::create(['production_id' => $s['production']->id, 'item_id' => $s['camA']->id, 'cam_number' => 'Cam 1', 'tripod' => $s['tripod']->id]);

    $this->actingAs($s['admin'])->put(route('camera-config.update', $a), [
        'cam_number' => 'Cam 1 neu', 'camera' => $s['camA']->id, 'tripod' => $s['tripod']->id,
    ])->assertSessionHasNoErrors()->assertSessionMissing('error');

    expect($a->fresh()->cam_number)->toBe('Cam 1 neu');
});

test('dasselbe Stativ in einer nicht überlappenden anderen Produktion bleibt erlaubt', function () {
    $s = rigSetup();
    CameraConfig::create(['production_id' => $s['production']->id, 'item_id' => $s['camA']->id, 'cam_number' => 'Cam 1', 'tripod' => $s['tripod']->id]);
    $later = Production::create(['bezeichnung' => 'Später', 'booking_start' => '2026-09-01', 'booking_end' => '2026-09-02']);

    $this->actingAs($s['admin'])->post(route('camera-config.store', $later), [
        'cam_number' => 'Cam 1', 'camera' => $s['camB']->id, 'tripod' => $s['tripod']->id,
    ])->assertSessionMissing('error');

    expect(CameraConfig::where('production_id', $later->id)->count())->toBe(1);
});

test('Kamerazug mit einem Gerät, das einzeln in der Produktion steht, wird abgelehnt', function () {
    $s = rigSetup();
    $s['production']->items()->attach($s['tripod']->id);

    $this->actingAs($s['admin'])->post(route('camera-config.store', $s['production']), [
        'cam_number' => 'Cam 1', 'camera' => $s['camA']->id, 'tripod' => $s['tripod']->id,
    ])->assertSessionHas('error', fn ($m) => str_contains($m, 'Stativ 1') && str_contains($m, 'Einzelgerät'));

    expect(CameraConfig::count())->toBe(0);
});

test('Einzelgerät, das schon in einem Kamerazug derselben Produktion steckt, wird abgelehnt', function () {
    $s = rigSetup();
    CameraConfig::create(['production_id' => $s['production']->id, 'item_id' => $s['camA']->id, 'cam_number' => 'Cam 1', 'tripod' => $s['tripod']->id]);

    $this->actingAs($s['admin'])->post(route('productions.attachItem', $s['production']), [
        'item_id' => [$s['tripod']->id],
    ]);

    expect($s['production']->items()->count())->toBe(0);
});

test('Kamerazug-Import überspringt Geräte, die in der Zielproduktion schon einzeln stehen', function () {
    $s = rigSetup();
    $source = Production::create(['bezeichnung' => 'Quelle', 'booking_start' => '2026-01-01', 'booking_end' => '2026-01-02']);
    $cfg = CameraConfig::create(['production_id' => $source->id, 'item_id' => $s['camA']->id, 'cam_number' => 'Cam 1', 'tripod' => $s['tripod']->id]);
    $s['production']->items()->attach($s['tripod']->id);

    $this->actingAs($s['admin'])->post(route('productions.storeImport', [$s['production'], $source]), [
        'configs' => [$cfg->id => 'import'],
    ]);

    expect(CameraConfig::where('production_id', $s['production']->id)->count())->toBe(0);
});

test('Formular graut Geräte aus, die in einem anderen Kamerazug dieser Produktion stecken', function () {
    $s = rigSetup();
    CameraConfig::create(['production_id' => $s['production']->id, 'item_id' => $s['camA']->id, 'cam_number' => 'Cam 1', 'tripod' => $s['tripod']->id]);

    $html = $this->actingAs($s['admin'])->get(route('camera-config.create', $s['production']))->assertOk()->getContent();

    expect($html)->toMatch('/<option value="'.$s['tripod']->id.'"[^>]*disabled[^>]*>[^<]*Stativ 1[^<]*Cam 1/s')
        ->and($html)->not->toMatch('/<option value="'.$s['camB']->id.'"[^>]*disabled/');
});

test('Formular graut auch Einzelgeräte und Geräte aus überlappenden anderen Produktionen aus', function () {
    $s = rigSetup();
    $s['production']->items()->attach($s['lens']->id);
    $other = Production::create(['bezeichnung' => 'Parallel', 'booking_start' => '2026-08-02', 'booking_end' => '2026-08-03']);
    $other->items()->attach($s['camB']->id);

    $html = $this->actingAs($s['admin'])->get(route('camera-config.create', $s['production']))->getContent();

    expect($html)->toMatch('/<option value="'.$s['lens']->id.'"[^>]*disabled/s')
        ->and($html)->toMatch('/<option value="'.$s['camB']->id.'"[^>]*disabled[^>]*>[^<]*Parallel/s');
});

test('beim Bearbeiten sind die eigenen Geräte des Kamerazugs nicht ausgegraut', function () {
    $s = rigSetup();
    $a = CameraConfig::create(['production_id' => $s['production']->id, 'item_id' => $s['camA']->id, 'cam_number' => 'Cam 1', 'tripod' => $s['tripod']->id]);

    $html = $this->actingAs($s['admin'])->get(route('camera-config.edit', $a))->assertOk()->getContent();

    expect($html)->not->toMatch('/<option value="'.$s['tripod']->id.'"[^>]*disabled/')
        ->and($html)->not->toMatch('/<option value="'.$s['camA']->id.'"[^>]*disabled/');
});
