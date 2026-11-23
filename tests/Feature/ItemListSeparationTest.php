<?php

use App\Models\Item;
use App\Models\Mietvorgang;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;

function listSetup(): array
{
    // Die Tabellenansicht je Gruppe hängt an festen Gruppen-IDs (1 Kameras): explizit, wegen MySQL-AUTO_INCREMENT.
    $unit = Unit::forceCreate(['id' => 1, 'bezeichnung' => 'Kameras']);
    $supplier = Supplier::create(['bezeichnung' => 'Pixmade Verleih']);

    return [
        'admin' => User::factory()->create(['role' => 'admin']),
        'unit' => $unit,
        'supplier' => $supplier,
        'own' => Item::create(['units_id' => $unit->id, 'bezeichnung' => 'Eigene-Kamera']),
        'rented' => Item::create(['units_id' => $unit->id, 'bezeichnung' => 'Miet-Kamera', 'suppliers_id' => $supplier->id]),
    ];
}

test('Geräte zeigt nur eigene Geräte', function () {
    $s = listSetup();

    $this->actingAs($s['admin'])->get(route('items.index'))
        ->assertOk()->assertSee('Eigene-Kamera')->assertDontSee('Miet-Kamera');
});

test('Mietgeräte zeigt nur gemietete Geräte mit Vermieter', function () {
    $s = listSetup();

    $this->actingAs($s['admin'])->get(route('items.rented'))
        ->assertOk()->assertSee('Miet-Kamera')->assertSee('Pixmade Verleih')
        ->assertDontSee('Eigene-Kamera')->assertSee('Mietgeräte');
});

test('Vermieter setzen verschiebt das Gerät, Entfernen holt es zurück', function () {
    $s = listSetup();
    $payload = fn (array $extra = []) => array_merge(['units_id' => $s['unit']->id, 'bezeichnung' => 'Eigene-Kamera'], $extra);

    $this->actingAs($s['admin'])->put(route('items.update', $s['own']), $payload([
        'suppliers_id' => $s['supplier']->id, 'rent_start' => '01.12.2026', 'rent_end' => '05.12.2026',
    ]))->assertSessionHasNoErrors();

    $this->actingAs($s['admin'])->get(route('items.index'))->assertDontSee('Eigene-Kamera');
    $this->actingAs($s['admin'])->get(route('items.rented'))->assertSee('Eigene-Kamera');

    $this->actingAs($s['admin'])->put(route('items.update', $s['own']), $payload(['suppliers_id' => '']))->assertSessionHasNoErrors();

    $this->actingAs($s['admin'])->get(route('items.index'))->assertSee('Eigene-Kamera');
});

test('Speichern eines Mietgeräts führt zur Mietgeräte-Liste', function () {
    $s = listSetup();

    $this->actingAs($s['admin'])->put(route('items.update', $s['rented']), [
        'units_id' => $s['unit']->id, 'bezeichnung' => 'Miet-Kamera', 'suppliers_id' => $s['supplier']->id,
        'rent_start' => '01.12.2026', 'rent_end' => '05.12.2026',
    ])->assertRedirect(route('items.rented'));

    $this->actingAs($s['admin'])->put(route('items.update', $s['own']), ['units_id' => $s['unit']->id, 'bezeichnung' => 'Eigene-Kamera'])
        ->assertRedirect(route('items.index'));
});

test('Mietgerät ohne Mietvorgang zeigt "noch nicht gemietet", mit Mietvorgang den Zeitraum', function () {
    $s = listSetup();

    $this->actingAs($s['admin'])->get(route('items.rented'))->assertSee('noch nicht gemietet');

    $mv = Mietvorgang::create(['suppliers_id' => $s['supplier']->id, 'rent_start' => '2026-12-01', 'rent_end' => '2026-12-05']);
    $mv->items()->attach($s['rented']->id);

    $this->actingAs($s['admin'])->get(route('items.rented'))
        ->assertSee('01.12.2026')->assertSee('05.12.2026')->assertDontSee('noch nicht gemietet');
});

test('Sortier- und Filterlinks bleiben auf der jeweiligen Seite', function () {
    $s = listSetup();

    foreach (['items.rented' => 'items.index', 'items.index' => 'items.rented'] as $page => $other) {
        foreach ([[], ['unit_id' => $s['unit']->id]] as $params) {
            $html = $this->actingAs($s['admin'])->get(route($page, $params))->assertOk()->getContent();
            $mine = route($page);
            $theirs = route($other);

            expect($html)->toContain('action="'.$mine.'"');
            // Kein Formular und kein Sortierkopf/Reset-Link zeigt auf die andere Liste.
            expect($html)->not->toContain('action="'.$theirs.'"');
            expect($html)->not->toMatch('/href="'.preg_quote($theirs, '/').'\?(sort_by|sort_direction)[^"]*"/');
        }
    }
});

test('Gruppenfilter funktioniert in beiden Listen', function () {
    $s = listSetup();
    $other = Unit::create(['bezeichnung' => 'Audio']);
    Item::create(['units_id' => $other->id, 'bezeichnung' => 'Eigenes-Mikro']);
    Item::create(['units_id' => $other->id, 'bezeichnung' => 'Miet-Mikro', 'suppliers_id' => $s['supplier']->id]);

    $this->actingAs($s['admin'])->get(route('items.index', ['unit_id' => $other->id]))
        ->assertSee('Eigenes-Mikro')->assertDontSee('Eigene-Kamera')->assertDontSee('Miet-Mikro');
    $this->actingAs($s['admin'])->get(route('items.rented', ['unit_id' => $other->id]))
        ->assertSee('Miet-Mikro')->assertDontSee('Miet-Kamera')->assertDontSee('Eigenes-Mikro');
});

test('Kameragruppe (Tabellenansicht) funktioniert in beiden Listen', function () {
    $s = listSetup();

    $this->actingAs($s['admin'])->get(route('items.index', ['unit_id' => 1]))->assertOk()->assertSee('Eigene-Kamera');
    $this->actingAs($s['admin'])->get(route('items.rented', ['unit_id' => 1]))->assertOk()->assertSee('Miet-Kamera');
});

test('Viewer sieht beide Listen ohne Anlege-Buttons', function () {
    listSetup();
    $viewer = User::factory()->create(['role' => 'viewer']);

    foreach (['items.index', 'items.rented'] as $route) {
        $this->actingAs($viewer)->get(route($route))->assertOk()->assertDontSee('Neues Gerät');
    }
});

test('der Filter Herkunft ist weg und ein alter Link ist harmlos', function () {
    $s = listSetup();

    $this->actingAs($s['admin'])->get(route('items.index'))->assertDontSee('name="herkunft"', false);
    $this->actingAs($s['admin'])->get(route('items.index', ['herkunft' => 'gemietet']))
        ->assertOk()->assertSee('Eigene-Kamera')->assertDontSee('Miet-Kamera');
});

test('Zurück und Abbrechen führen von einem Mietgerät zur Mietgeräte-Liste, von einem eigenen Gerät zu Geräte', function () {
    $s = listSetup();
    $back = fn (string $route) => '/href="'.preg_quote($route, '/').'"[^>]*>\\s*(Zurück|Abbrechen)/';

    foreach (['items.show', 'items.edit'] as $route) {
        $rented = $this->actingAs($s['admin'])->get(route($route, $s['rented']))->assertOk()->getContent();
        expect($rented)->toMatch($back(route('items.rented')))->not->toMatch($back(route('items.index')));

        $own = $this->actingAs($s['admin'])->get(route($route, $s['own']))->assertOk()->getContent();
        expect($own)->toMatch($back(route('items.index')))->not->toMatch($back(route('items.rented')));
    }
});
