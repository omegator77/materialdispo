<?php

use App\Models\Item;
use App\Models\Unit;
use App\Models\User;

/**
 * Positionen der beiden Testgeräte im gerenderten HTML ablesen, um ihre
 * Reihenfolge zu prüfen.
 */
function itemPositions(string $content): array
{
    return [
        'zebra' => strpos($content, 'Gerät Zebra'),
        'alpha' => strpos($content, 'Gerät Alpha'),
    ];
}

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'user']);

    // Gruppe B liegt via sort_order VOR Gruppe A, obwohl sie alphabetisch
    // hinten und (hier) mit kleinerer ID angelegt ist.
    $this->groupB = Unit::create(['bezeichnung' => 'Zebra-Gruppe', 'sort_order' => 1]);
    $this->groupA = Unit::create(['bezeichnung' => 'Alpha-Gruppe', 'sort_order' => 2]);

    $this->itemInB = Item::create(['bezeichnung' => 'Gerät Zebra', 'units_id' => $this->groupB->id]);
    $this->itemInA = Item::create(['bezeichnung' => 'Gerät Alpha', 'units_id' => $this->groupA->id]);
});

test('Standard-Sortierung folgt der konfigurierten Gruppen-Reihenfolge (sort_order), nicht dem Alphabet', function () {
    $content = $this->actingAs($this->user)->get(route('items.index'))->getContent();

    $pos = itemPositions($content);

    // Zebra-Gruppe (sort_order 1) vor Alpha-Gruppe (sort_order 2).
    expect($pos['zebra'])->not->toBeFalse();
    expect($pos['zebra'])->toBeLessThan($pos['alpha']);
});

test('Gruppe aufsteigend sortiert alphabetisch nach Gruppenname', function () {
    $content = $this->actingAs($this->user)
        ->get(route('items.index', ['sort_by' => 'group', 'sort_direction' => 'asc']))
        ->getContent();

    $pos = itemPositions($content);

    // Alpha-Gruppe vor Zebra-Gruppe.
    expect($pos['alpha'])->toBeLessThan($pos['zebra']);
});

test('Gruppe absteigend kehrt die alphabetische Reihenfolge um', function () {
    $content = $this->actingAs($this->user)
        ->get(route('items.index', ['sort_by' => 'group', 'sort_direction' => 'desc']))
        ->getContent();

    $pos = itemPositions($content);

    expect($pos['zebra'])->toBeLessThan($pos['alpha']);
});
