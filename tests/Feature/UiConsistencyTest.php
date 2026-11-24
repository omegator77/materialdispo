<?php

use App\Models\Item;
use App\Models\Production;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Einheitliche Oberfläche: Jede umgestellte Seite nutzt dasselbe Seitenraster (.page, fluchtet mit
 * dem Seitenkopf), denselben Seitenkopf (<x-page-header>) und die gemeinsamen Knopf-/Karten-Klassen
 * statt der früheren Einzelfall-Klassen.
 */
dataset('listenseiten', [
    'Dashboard' => ['dashboard'],
    'Geräte' => ['items.index'],
    'Mietgeräte' => ['items.rented'],
    'Produktionen' => ['productions.index'],
    'Lieferanten' => ['suppliers.index'],
    'Mieter' => ['mieter.index'],
    'Gruppen' => ['units.index'],
    'Gerätetypen' => ['geraetetypen.index'],
    'Benutzer' => ['users.index'],
    'Mailinglisten' => ['mailing-lists.index'],
    'Mietvorgänge' => ['mietvorgaenge.index'],
    'Vermietvorgänge' => ['vermietvorgaenge.index'],
    'Packliste' => ['itemprods'],
    'Aktivitätslog' => ['activity-log.index'],
    'Bestellungen' => ['bestellungen.index'],
]);

// Reste der alten, uneinheitlichen Gestaltung; auf umgestellten Seiten darf nichts davon vorkommen.
const UI_ALTLASTEN = [
    'bg-orange-400',
    'hover:bg-orange-500',
    'bg-gray-200 hover:bg-gray-300',
    'max-w-5xl w-11/12',
    'max-w-7xl w-11/12',
    'border-gray-300 rounded-lg shadow-md',
    'bg-gray-100 border-b',
];

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $unit = Unit::create(['bezeichnung' => 'Kameras']);
    Item::create(['units_id' => $unit->id, 'bezeichnung' => 'Kamera', 'nummer' => 'K-1']);
    Production::create(['bezeichnung' => 'Prod', 'booking_start' => '2026-12-01', 'booking_end' => '2026-12-02']);
});

test('Listenseiten nutzen das gemeinsame Seitenraster und den einheitlichen Seitenkopf', function (string $route) {
    $html = $this->actingAs($this->admin)->get(route($route))->assertOk()->getContent();

    expect($html)->toContain('data-page-header')
        ->and($html)->toContain('class="page');
})->with('listenseiten');

test('Listenseiten enthalten keine Altlasten der früheren Einzelgestaltung', function (string $route) {
    $html = $this->actingAs($this->admin)->get(route($route))->assertOk()->getContent();

    foreach (UI_ALTLASTEN as $alt) {
        // Bewusst assertFalse: not->toContain($nadel, $meldung) behandelt die Meldung als zweite
        // Nadel und besteht schon, wenn nicht ALLE Nadeln enthalten sind.
        $this->assertFalse(str_contains($html, $alt), "Altlast '{$alt}' auf {$route}");
    }
})->with('listenseiten');

test('Listenseiten sind auch für Betrachter erreichbar und einheitlich', function () {
    $viewer = User::factory()->create(['role' => 'viewer']);

    foreach (['dashboard', 'items.index', 'productions.index', 'suppliers.index', 'units.index'] as $route) {
        $this->actingAs($viewer)->get(route($route))->assertOk()->assertSee('data-page-header', false);
    }
});

test('Zeitangaben im Dashboard sind deutsch', function () {
    activity()->causedBy($this->admin)->log('Test');

    $this->actingAs($this->admin)->get(route('dashboard'))->assertOk()
        ->assertDontSee(' ago', false)
        ->assertSee('vor ', false);

    expect(Carbon::getLocale())->toBe('de');
});

test('die Produktionsliste hat einen deutschen Titel', function () {
    $this->actingAs($this->admin)->get(route('productions.index'))
        ->assertOk()->assertSee('Produktionen')->assertDontSee('Productions');
});
