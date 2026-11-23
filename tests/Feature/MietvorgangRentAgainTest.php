<?php

use App\Models\Item;
use App\Models\Mietvorgang;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;

function ragSetup(): array
{
    $unit = Unit::create(['bezeichnung' => 'Kameras']);
    $supplier = Supplier::create(['bezeichnung' => 'Pixmade']);
    $item = Item::create(['units_id' => $unit->id, 'bezeichnung' => 'Alt-Kamera', 'nummer' => 'K-9', 'suppliers_id' => $supplier->id]);
    $mv = Mietvorgang::create([
        'suppliers_id' => $supplier->id, 'rent_start' => '2026-01-01', 'rent_end' => '2026-01-05',
        'transport_end_confirmed_at' => now(), 'bereit_zur_rueckgabe_confirmed_at' => now(),
    ]);
    $mv->items()->attach($item->id);

    return ['admin' => User::factory()->create(['role' => 'admin']), 'unit' => $unit, 'supplier' => $supplier, 'item' => $item];
}

function rentAgainUrl(array $s): string
{
    return route('mietvorgaenge.create', ['suppliers_id' => $s['supplier']->id, 'item_id' => [$s['item']->id]]);
}

beforeEach(fn () => $this->travelTo('2026-12-10'));

test('Archivzeile zeigt "Erneut mieten" mit vorbelegtem Link, Viewer nicht', function () {
    $s = ragSetup();

    $this->actingAs($s['admin'])->get(route('items.rented'))
        ->assertSee('Erneut mieten')->assertSee(e(rentAgainUrl($s)), false);

    $this->actingAs(User::factory()->create(['role' => 'viewer']))->get(route('items.rented'))
        ->assertDontSee('Erneut mieten');
});

test('das Formular ist mit Vermieter und Gerät vorbelegt', function () {
    $s = ragSetup();

    $html = $this->actingAs($s['admin'])->get(rentAgainUrl($s))->assertOk()->getContent();

    expect($html)->toMatch('/<option value="'.$s['supplier']->id.'"[^>]*\bselected\b/');
    expect($html)->toMatch('/selected:\s*\[\s*\{\s*id:\s*'.$s['item']->id.'\b/');
    expect($html)->toContain('Alt-Kamera (K-9)');
});

test('ungültige Werte werden ignoriert', function () {
    $s = ragSetup();
    $other = Supplier::create(['bezeichnung' => 'Anderer']);
    $own = Item::create(['units_id' => $s['unit']->id, 'bezeichnung' => 'Eigene-Kamera']);
    $fremd = Item::create(['units_id' => $s['unit']->id, 'bezeichnung' => 'Fremd-Kamera', 'suppliers_id' => $other->id]);

    $url = fn (array $q) => route('mietvorgaenge.create', $q);

    // unbekannter Vermieter, Text statt ID
    foreach ([['suppliers_id' => 99999, 'item_id' => [$s['item']->id]], ['suppliers_id' => 'abc', 'item_id' => ['x']], ['item_id' => 'kaputt']] as $q) {
        $html = $this->actingAs($s['admin'])->get($url($q))->assertOk()->getContent();
        expect($html)->not->toMatch('/<option value="'.$s['supplier']->id.'"[^>]*\bselected\b/');
        expect($html)->toMatch('/selected:\s*\[\s*\]/');
    }

    // Gerät eines anderen Vermieters und Nicht-Mietgerät werden nicht vorgewählt
    $html = $this->actingAs($s['admin'])->get($url(['suppliers_id' => $s['supplier']->id, 'item_id' => [$fremd->id, $own->id, $s['item']->id]]))
        ->assertOk()->getContent();
    expect($html)->toMatch('/selected:\s*\[\s*\{\s*id:\s*'.$s['item']->id.'\b[^\]]*\]/s');
    expect(preg_match('/selected:\s*\[(.*?)\]\s*,?\s*get/s', $html, $m))->toBe(1);
    expect($m[1])->not->toContain('Fremd-Kamera')->not->toContain('Eigene-Kamera');
});

test('ein neuer Mietvorgang holt das Gerät aus dem Archiv zurück', function () {
    $s = ragSetup();

    expect($this->actingAs($s['admin'])->get(route('items.rented'))->getContent())->toContain('Archiv (1)');

    $this->actingAs($s['admin'])->post(route('mietvorgaenge.store'), [
        'suppliers_id' => $s['supplier']->id, 'rent_start' => '15.12.2026', 'rent_end' => '20.12.2026',
        'item_id' => [$s['item']->id],
    ])->assertSessionHasNoErrors();

    $html = $this->actingAs($s['admin'])->get(route('items.rented'))->getContent();

    expect($html)->toContain('Alt-Kamera')->toContain('15.12.2026')->not->toContain('Archiv (')->not->toContain('data-rent-archive');
});

test('Viewer erhält 403 auf das Anlege-Formular', function () {
    $s = ragSetup();

    $this->actingAs(User::factory()->create(['role' => 'viewer']))->get(rentAgainUrl($s))->assertForbidden();
});

test('je Gerät genau ein Button "Erneut mieten" in der Desktop- und eine in der Mobilansicht', function () {
    $s = ragSetup();

    $archiv = substr($this->actingAs($s['admin'])->get(route('items.rented'))->getContent(), 0);
    $archiv = substr($archiv, (int) strpos($archiv, 'data-rent-archive'));
    [$desktop, $mobile] = explode('md:hidden', $archiv, 2);

    expect(substr_count($desktop, 'Erneut mieten'))->toBe(1)->and(substr_count($mobile, 'Erneut mieten'))->toBe(1);
});
