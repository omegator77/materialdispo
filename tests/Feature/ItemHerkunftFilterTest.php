<?php

use App\Models\Item;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;

function herkunftSetup(): void
{
    $unit = Unit::create(['bezeichnung' => 'Kameras']);
    $supplier = Supplier::create(['bezeichnung' => 'Verleih']);
    Item::create(['units_id' => $unit->id, 'bezeichnung' => 'Miet-Kamera', 'suppliers_id' => $supplier->id]);
    Item::create(['units_id' => $unit->id, 'bezeichnung' => 'Eigene-Kamera']);
}

test('Herkunft gemietet zeigt nur Geräte mit Vermieter', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    herkunftSetup();

    $this->actingAs($admin)->get(route('items.index', ['herkunft' => 'gemietet']))
        ->assertOk()->assertSee('Miet-Kamera')->assertDontSee('Eigene-Kamera');
});

test('Herkunft eigen zeigt nur Geräte ohne Vermieter', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    herkunftSetup();

    $this->actingAs($admin)->get(route('items.index', ['herkunft' => 'eigen']))
        ->assertOk()->assertSee('Eigene-Kamera')->assertDontSee('Miet-Kamera');
});

test('ohne oder mit ungültiger Herkunft werden alle gezeigt', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    herkunftSetup();

    foreach ([[], ['herkunft' => 'quatsch']] as $params) {
        $this->actingAs($admin)->get(route('items.index', $params))
            ->assertOk()->assertSee('Miet-Kamera')->assertSee('Eigene-Kamera');
    }
});

test('Filterleiste bietet die Herkunft an und behält die Auswahl', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get(route('items.index', ['herkunft' => 'gemietet']))
        ->assertSee('name="herkunft"', false)
        ->assertSee('Nur Gemietetes')
        ->assertSee('<option value="gemietet" selected', false)
        ->assertSee('Filter zurücksetzen');
});
