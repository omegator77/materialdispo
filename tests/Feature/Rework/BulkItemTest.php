<?php

use App\Models\Geraetetyp;
use App\Models\Item;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Services\BulkItemService;

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'user']);
    $this->unit = Unit::create(['bezeichnung' => 'Kameras']);
    $this->typ = Geraetetyp::create(['units_id' => $this->unit->id, 'bezeichnung' => 'LDX 86N']);
});

test('number specs expand ranges and lists', function () {
    $bulk = app(BulkItemService::class);

    expect($bulk->parseNumberSpec('1-3, 8, 21-22'))->toBe(['1', '2', '3', '8', '21', '22']);
    expect($bulk->parseNumberSpec('001-003'))->toBe(['001', '002', '003']);
    expect($bulk->parseNumberSpec('A12 B7'))->toBe(['A12', 'B7']);
    expect($bulk->parseNumberSpec(''))->toBe([]);
});

test('next number continues after the highest numeric number in the group', function () {
    Item::create(['units_id' => $this->unit->id, 'bezeichnung' => 'x', 'nummer' => '7']);
    Item::create(['units_id' => $this->unit->id, 'bezeichnung' => 'y', 'nummer' => 'A1']);

    expect(app(BulkItemService::class)->suggestNextNumber($this->unit->id))->toBe('8');
});

test('several devices are created at once with sequential numbers', function () {
    Item::create(['units_id' => $this->unit->id, 'bezeichnung' => 'alt', 'nummer' => '4']);

    $this->actingAs($this->user)->post(route('items.bulk.store'), ['rows' => [
        ['geraetetyp_id' => $this->typ->id, 'units_id' => $this->unit->id, 'anzahl' => 3],
    ]])->assertRedirect(route('items.index'));

    $created = Item::where('geraetetyp_id', $this->typ->id)->orderBy('nummer')->get();
    expect($created->pluck('nummer')->all())->toBe(['5', '6', '7'])
        ->and($created->pluck('bezeichnung')->unique()->all())->toBe(['LDX 86N']);
});

test('explicit number ranges win over count and duplicates are skipped', function () {
    Item::create(['units_id' => $this->unit->id, 'geraetetyp_id' => $this->typ->id, 'bezeichnung' => 'LDX 86N', 'nummer' => '22']);

    $this->actingAs($this->user)->post(route('items.bulk.store'), ['rows' => [
        ['geraetetyp_id' => $this->typ->id, 'units_id' => $this->unit->id, 'nummern' => '21-23'],
    ]])->assertSessionHas('warning');

    expect(Item::where('geraetetyp_id', $this->typ->id)->pluck('nummer')->sort()->values()->all())->toBe(['21', '22', '23']);
});

test('rented rows share one Mietvorgang', function () {
    $supplier = Supplier::create(['bezeichnung' => 'Pixmade']);

    $this->actingAs($this->user)->post(route('items.bulk.store'), ['rows' => [[
        'geraetetyp_id' => $this->typ->id, 'units_id' => $this->unit->id, 'anzahl' => 3,
        'suppliers_id' => $supplier->id, 'rent_start' => '01.12.2026', 'rent_end' => '10.12.2026',
    ]]])->assertSessionHasNoErrors();

    $items = Item::where('suppliers_id', $supplier->id)->with('mietvorgaenge')->get();
    expect($items)->toHaveCount(3)
        ->and($items->flatMap->mietvorgaenge->pluck('id')->unique())->toHaveCount(1);
});

test('rented row without period is rejected', function () {
    $supplier = Supplier::create(['bezeichnung' => 'Pixmade']);

    $this->actingAs($this->user)->post(route('items.bulk.store'), ['rows' => [[
        'geraetetyp_id' => $this->typ->id, 'units_id' => $this->unit->id, 'anzahl' => 1, 'suppliers_id' => $supplier->id,
    ]]])->assertSessionHasErrors();

    expect(Item::count())->toBe(0);
});

test('free text is parsed into prefilled rows', function () {
    $this->actingAs($this->user)
        ->postJson(route('items.bulk.parse'), ['text' => '3x LDX 86N #21-23'])
        ->assertOk()
        ->assertJsonPath('rows.0.geraetetyp_id', $this->typ->id)
        ->assertJsonPath('rows.0.anzahl', 3)
        ->assertJsonPath('rows.0.nummern', '21-23');
});

test('viewers cannot bulk create', function () {
    $viewer = User::factory()->create(['role' => 'viewer']);

    $this->actingAs($viewer)->get(route('items.bulk.create'))->assertForbidden();
    $this->actingAs($viewer)->post(route('items.bulk.store'), [])->assertForbidden();
});

test('nur gemietete Zeilen führen zur Mietgeräte-Liste, gemischte zu Geräte mit Hinweis', function () {
    $supplier = Supplier::create(['bezeichnung' => 'Pixmade']);
    $rented = ['geraetetyp_id' => $this->typ->id, 'units_id' => $this->unit->id, 'anzahl' => 2,
        'suppliers_id' => $supplier->id, 'rent_start' => '01.12.2026', 'rent_end' => '10.12.2026'];
    $own = ['geraetetyp_id' => $this->typ->id, 'units_id' => $this->unit->id, 'anzahl' => 1];

    $this->actingAs($this->user)->post(route('items.bulk.store'), ['rows' => [$rented]])
        ->assertRedirect(route('items.rented'));

    $this->actingAs($this->user)->post(route('items.bulk.store'), ['rows' => [$rented, $own]])
        ->assertRedirect(route('items.index'))
        ->assertSessionHas('success', fn ($m) => str_contains($m, 'Mietgerät') && str_contains($m, 'Mietgeräte'));
});
