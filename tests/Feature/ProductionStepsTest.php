<?php

use App\Models\Item;
use App\Models\Production;
use App\Models\ProductionItemPack;
use App\Models\Unit;
use App\Models\User;
use App\Models\VbProtokoll;
use App\Services\ProductionAssignmentService;
use Illuminate\Support\Facades\Blade;

function stepsProduction(string $name = 'Tagesschau', int $items = 0): Production
{
    $p = Production::create(['bezeichnung' => $name, 'booking_start' => '2026-12-01', 'booking_end' => '2026-12-02']);
    $unit = Unit::create(['bezeichnung' => 'Kameras '.$name]);
    $created = [];
    for ($i = 0; $i < $items; $i++) {
        $created[] = Item::create(['units_id' => $unit->id, 'bezeichnung' => "Gerät $i"]);
    }
    if ($created) {
        app(ProductionAssignmentService::class)->attachMany($p, $created);
    }

    return $p->fresh();
}

function renderSteps(Production $p, string $role = 'admin', string $active = 'packliste'): string
{
    $user = User::factory()->create(['role' => $role]);

    test()->actingAs($user);

    return Blade::render('<x-production-steps :production="$p" active="'.$active.'" />', ['p' => $p]);
}

test('stepStatus einer leeren Produktion', function () {
    expect(stepsProduction()->stepStatus())->toBe(['vb' => false, 'packliste' => 0, 'packed' => 0, 'confirmed' => false]);
});

test('stepStatus zählt Geräte, gepackte und VB-Protokoll', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $p = stepsProduction('P', 3);
    foreach ($p->items->take(2) as $item) {
        ProductionItemPack::create(['production_id' => $p->id, 'item_id' => $item->id, 'packed_by' => $admin->id, 'packed_at' => now()]);
    }
    VbProtokoll::create(['production_id' => $p->id, 'created_by' => $admin->id]);

    expect($p->fresh()->stepStatus())->toBe(['vb' => true, 'packliste' => 3, 'packed' => 2, 'confirmed' => false]);
});

test('Leiste zeigt vier Schritte in Reihenfolge mit Stand und genau einem aktiven Schritt', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $p = stepsProduction('P', 3);
    ProductionItemPack::create(['production_id' => $p->id, 'item_id' => $p->items->first()->id, 'packed_by' => $admin->id, 'packed_at' => now()]);
    ProductionItemPack::create(['production_id' => $p->id, 'item_id' => $p->items->last()->id, 'packed_by' => $admin->id, 'packed_at' => now()]);

    $html = renderSteps($p->fresh());

    expect($html)->toContain('data-production-steps')->toContain('2/3');
    $pos = array_map(fn ($n) => strpos($html, $n), ['Details', 'VB-Protokoll', 'Packliste', 'Packen']);
    expect($pos)->each->not->toBeFalse();
    expect($pos)->toBe(collect($pos)->sort()->values()->all());
    expect(substr_count($html, 'aria-current="step"'))->toBe(1);
});

test('ohne VB-Protokoll sieht Admin den Anlegen-Link, Viewer nur fehlt', function () {
    $p = stepsProduction();
    $create = route('vb-protokoll.create', $p);

    expect(renderSteps($p, 'admin'))->toContain($create);
    $viewer = renderSteps($p, 'viewer');
    expect($viewer)->toContain('fehlt')->not->toContain($create);
});

test('abgeschlossen zeigt Stand und Kennzeichen', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $p = stepsProduction('P', 3);
    foreach ($p->items->take(2) as $item) {
        ProductionItemPack::create(['production_id' => $p->id, 'item_id' => $item->id, 'packed_by' => $admin->id, 'packed_at' => now()]);
    }
    $p->forceFill(['packvorgang_confirmed_at' => now()])->save();

    expect(renderSteps($p->fresh()))->toContain('2/3')->toContain('abgeschlossen');
});

test('leere Produktion zeigt 0/0 ohne abgeschlossen', function () {
    expect(renderSteps(stepsProduction()))->toContain('0/0')->not->toContain('abgeschlossen');
});

test('Produktionsname wird maskiert', function () {
    $p = stepsProduction('Müller & <b>Co</b>');
    $html = renderSteps($p);

    expect($html)->not->toContain('<b>Co</b>')->toContain('Müller &amp; &lt;b&gt;Co&lt;/b&gt;');
});
