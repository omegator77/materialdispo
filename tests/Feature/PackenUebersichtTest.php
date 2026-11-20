<?php

use App\Models\Item;
use App\Models\Production;
use App\Models\Unit;
use App\Models\User;

function packenProduction(string $name, string $start, bool $confirmed = false): Production
{
    $p = Production::create([
        'bezeichnung' => $name, 'booking_start' => $start, 'booking_end' => $start,
        'packvorgang_confirmed_at' => $confirmed ? now() : null,
    ]);
    $unit = Unit::firstOrCreate(['bezeichnung' => 'Kameras']);
    $item = Item::create(['units_id' => $unit->id, 'bezeichnung' => "{$name} Gerät"]);
    $p->items()->attach($item->id);

    return $p->fresh();
}

test('Offene Produktionen stehen vor abgeschlossenen', function () {
    packenProduction('ProdBeta', '2027-01-01', true);
    packenProduction('ProdAlpha', '2027-02-01');
    packenProduction('ProdGamma', '2027-03-01');

    $html = $this->actingAs(User::factory()->create(['role' => 'user']))
        ->get(route('itemprods'))->assertOk()->getContent();

    // Karten stehen nach dem Filter-Dropdown; Dropdown folgt derselben Reihenfolge
    $cards = substr($html, strpos($html, '<details'));
    $a = strpos($cards, 'ProdAlpha');
    $c = strpos($cards, 'ProdGamma');
    $b = strpos($cards, 'ProdBeta');
    expect($a)->toBeLessThan($c)->and($c)->toBeLessThan($b);
});

test('Produktionsfilter zeigt nur die gewählte Produktion', function () {
    $a = packenProduction('ProdAlpha', '2027-02-01');
    packenProduction('ProdGamma', '2027-03-01');

    $html = $this->actingAs(User::factory()->create(['role' => 'user']))
        ->get(route('itemprods', ['production_id' => $a->id]))->assertOk()->getContent();

    $cards = substr($html, strpos($html, '<details'));
    expect($cards)->toContain('ProdAlpha')->and($cards)->not->toContain('ProdGamma');
});

test('Karte hat primären Packen-Button mit Stand', function () {
    $a = packenProduction('ProdAlpha', '2027-02-01');

    $html = $this->actingAs(User::factory()->create(['role' => 'user']))
        ->get(route('itemprods'))->assertOk()->getContent();

    $pos = strpos($html, route('packvorgang.show', $a->id));
    expect($pos)->not->toBeFalse();
    $snippet = substr($html, max(0, $pos - 100), 500);
    expect($snippet)->toContain('btn btn-primary')->and($snippet)->toContain('0/1');
});
