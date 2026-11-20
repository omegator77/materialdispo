<?php

use App\Models\Item;
use App\Models\Mietvorgang;
use App\Models\Production;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Services\ProductionAssignmentService;
use Illuminate\Support\Str;

function layoutProduction(string $name = 'Tagesschau'): Production
{
    return Production::create(['bezeichnung' => $name, 'booking_start' => '2026-12-01', 'booking_end' => '2026-12-02']);
}

test('Schnelleingabe steht vor den Mietvorgängen', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $unit = Unit::create(['bezeichnung' => 'Kameras']);
    $supplier = Supplier::create(['bezeichnung' => 'Verleih']);
    $item = Item::create(['units_id' => $unit->id, 'bezeichnung' => 'Miet-Kamera', 'suppliers_id' => $supplier->id]);
    $p = layoutProduction();
    app(ProductionAssignmentService::class)->attachMany($p, [$item]);
    $mv = Mietvorgang::create(['suppliers_id' => $supplier->id, 'rent_start' => '2026-12-01', 'rent_end' => '2026-12-02']);
    $mv->items()->attach($item->id);

    $html = $this->actingAs($admin)->get(route('productions.show', $p))->assertOk()->getContent();

    expect($html)->toContain('Mietvorgänge (Transport)');
    expect(strpos($html, 'Schnell hinzufügen'))->toBeLessThan(strpos($html, 'Mietvorgänge (Transport)'));
});

test('VB-Abgleich steht im Quelltext hinter dem Material-Raster', function () {
    $src = file_get_contents(resource_path('views/productions/show.blade.php'));

    expect(strpos($src, 'Abgleich mit VB-Protokoll'))->toBeGreaterThan(strpos($src, 'lg:grid-cols-5'))
        ->and(strpos($src, 'Mietvorgänge (Transport)'))->toBeGreaterThan(strpos($src, 'lg:grid-cols-5'))
        ->and(strpos($src, 'Vermietvorgänge (Transport)'))->toBeGreaterThan(strpos($src, 'lg:grid-cols-5'));
});

test('leere Produktion zeigt den Leer-Zustand mit Schnelleingabe-Anker', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $p = layoutProduction();

    $this->actingAs($admin)->get(route('productions.show', $p))
        ->assertSee('data-empty-state', false)
        ->assertSee('href="#schnelleingabe"', false)
        ->assertSee('href="#material-hinzufuegen"', false)
        ->assertSee('id="schnelleingabe"', false);
});

test('Produktion mit Gerät zeigt keinen Leer-Zustand', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $unit = Unit::create(['bezeichnung' => 'Kameras']);
    $item = Item::create(['units_id' => $unit->id, 'bezeichnung' => 'Eigene Kamera']);
    $p = layoutProduction();
    app(ProductionAssignmentService::class)->attachMany($p, [$item]);

    $this->actingAs($admin)->get(route('productions.show', $p))->assertDontSee('data-empty-state', false);
});

test('Viewer sieht den Leer-Zustand nicht', function () {
    $viewer = User::factory()->create(['role' => 'viewer']);

    $this->actingAs($viewer)->get(route('productions.show', layoutProduction()))->assertDontSee('data-empty-state', false);
});

test('Leer-Zustand bietet Übernahme aus ähnlicher Produktion', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $unit = Unit::create(['bezeichnung' => 'Kameras']);
    $quelle = layoutProduction('Tagesschau Spezial');
    foreach (range(1, 3) as $i) {
        $it = Item::create(['units_id' => $unit->id, 'bezeichnung' => "Kamera $i"]);
        app(ProductionAssignmentService::class)->attachMany($quelle, [$it]);
    }
    $neu = layoutProduction('Tagesschau Spezial 2');

    $html = $this->actingAs($admin)->get(route('productions.show', $neu))->getContent();
    expect($html)->toContain('data-empty-state-end');
    $leer = Str::between($html, 'data-empty-state', 'data-empty-state-end');

    expect($leer)->toContain(e(route('productions.importFrom', [$neu, $quelle])));
});
