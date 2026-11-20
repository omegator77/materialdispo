<?php

use App\Models\Item;
use App\Models\Production;
use App\Models\ProductionItemPack;
use App\Models\Unit;
use App\Models\User;
use App\Models\VbProtokoll;
use Illuminate\Support\Facades\DB;

function listProduction(string $name, int $items = 0, int $packed = 0, array $attrs = []): Production
{
    $p = Production::create(array_merge([
        'bezeichnung' => $name, 'booking_start' => '2026-12-01', 'booking_end' => '2026-12-02',
    ], $attrs));
    $unit = Unit::firstOrCreate(['bezeichnung' => 'Kameras']);
    $user = User::factory()->create(['role' => 'admin']);

    foreach (range(1, max($items, 1)) as $i) {
        if ($i > $items) {
            break;
        }
        $item = Item::create(['units_id' => $unit->id, 'bezeichnung' => "{$name} Gerät {$i}"]);
        $p->items()->attach($item->id);
        if ($i <= $packed) {
            ProductionItemPack::create(['production_id' => $p->id, 'item_id' => $item->id, 'packed_by' => $user->id, 'packed_at' => now()]);
        }
    }

    return $p->fresh();
}

function listQueryCount(User $user): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();
    test()->actingAs($user)->get(route('productions.index'))->assertOk();
    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    return $count;
}

test('Liste zeigt Packstand und Link zum Packen', function () {
    $a = listProduction('Alpha', 3, 2);
    listProduction('Beta', 2, 0);

    $html = $this->actingAs(User::factory()->create(['role' => 'user']))
        ->get(route('productions.index'))->assertOk()->getContent();

    expect($html)->toContain('2/3')
        ->toContain('gepackt 2/3')
        ->toContain(route('packvorgang.show', $a))
        ->toContain(route('productions.show', $a));
});

test('Abgeschlossene Produktion zeigt abgeschlossen', function () {
    listProduction('Fertig', 2, 2, ['packvorgang_confirmed_at' => now()]);

    $this->actingAs(User::factory()->create(['role' => 'user']))
        ->get(route('productions.index'))->assertOk()->assertSee('abgeschlossen');
});

test('Leere Produktion zeigt 0/0 ohne Fehler', function () {
    listProduction('Leer');

    $this->actingAs(User::factory()->create(['role' => 'user']))
        ->get(route('productions.index'))->assertOk()->assertSee('0/0');
});

test('Viewer sieht den Stand, aber keinen Link zum Anlegen des VB-Protokolls', function () {
    $p = listProduction('Alpha', 3, 2);

    $html = $this->actingAs(User::factory()->create(['role' => 'viewer']))
        ->get(route('productions.index'))->assertOk()->getContent();

    expect($html)->toContain('2/3')->not->toContain(route('vb-protokoll.create', $p));
});

test('Die Abfragezahl der Liste wächst nicht mit der Zahl der Produktionen', function () {
    $user = User::factory()->create(['role' => 'user']);
    foreach (range(1, 2) as $i) {
        $p = listProduction("Klein {$i}", 2, 1);
        VbProtokoll::create(['production_id' => $p->id, 'created_by' => $user->id]);
    }
    $small = listQueryCount($user);

    foreach (range(1, 8) as $i) {
        $p = listProduction("Gross {$i}", 2, 1);
        VbProtokoll::create(['production_id' => $p->id, 'created_by' => $user->id]);
    }
    $large = listQueryCount($user);

    expect($large)->toBe($small);
});

test('stepStatus löst mit vorgeladenen Relationen keine weiteren Abfragen aus', function () {
    $p = listProduction('Alpha', 3, 2);
    $p->load(Production::LIST_RELATIONS);

    DB::flushQueryLog();
    DB::enableQueryLog();
    $status = $p->stepStatus();
    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($count)->toBe(0)->and($status['packliste'])->toBe(3)->and($status['packed'])->toBe(2);
});
