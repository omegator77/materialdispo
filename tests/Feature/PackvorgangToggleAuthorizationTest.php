<?php

use App\Models\Item;
use App\Models\Production;
use App\Models\Unit;
use App\Models\User;

function packScenario(): array
{
    $unit = Unit::create(['bezeichnung' => 'Kameras']);
    $item = Item::create(['units_id' => $unit->id, 'bezeichnung' => 'Kamera']);
    $production = Production::create(['bezeichnung' => 'Prod', 'booking_start' => '2026-08-01', 'booking_end' => '2026-08-02']);
    $production->items()->attach($item->id);

    return [$production, $item, $unit];
}

test('Viewer darf den Packstatus nicht ändern', function () {
    [$production, $item] = packScenario();
    $viewer = User::factory()->create(['role' => 'viewer']);

    $this->actingAs($viewer)
        ->postJson(route('packvorgang.toggle', [$production, $item]))
        ->assertForbidden();

    expect($production->itemPacks()->count())->toBe(0);
});

test('Benutzer mit Rolle user darf den Packstatus umschalten', function () {
    [$production, $item] = packScenario();
    $user = User::factory()->create(['role' => 'user']);

    $this->actingAs($user)
        ->postJson(route('packvorgang.toggle', [$production, $item]))
        ->assertOk()->assertJson(['packed' => true]);

    expect($production->itemPacks()->count())->toBe(1);
});

test('Viewer darf die Packliste weiterhin ansehen', function () {
    [$production] = packScenario();
    $viewer = User::factory()->create(['role' => 'viewer']);

    $this->actingAs($viewer)->get(route('packvorgang.show', $production))->assertOk();
});

test('Geräte, die nicht zur Packliste gehören, lassen sich nicht als gepackt markieren', function () {
    [$production, , $unit] = packScenario();
    $fremd = Item::create(['units_id' => $unit->id, 'bezeichnung' => 'Nicht auf der Liste']);
    $user = User::factory()->create(['role' => 'user']);

    $this->actingAs($user)
        ->postJson(route('packvorgang.toggle', [$production, $fremd]))
        ->assertNotFound();

    expect($production->itemPacks()->count())->toBe(0);
});

test('Packliste ist für Viewer schreibgeschützt dargestellt, für Benutzer nicht', function () {
    [$production] = packScenario();

    $this->actingAs(User::factory()->create(['role' => 'viewer']))
        ->get(route('packvorgang.show', $production))
        ->assertOk()->assertSee('Nur Lesezugriff')->assertSee('disabled', false);

    $this->actingAs(User::factory()->create(['role' => 'user']))
        ->get(route('packvorgang.show', $production))
        ->assertOk()->assertDontSee('Nur Lesezugriff');
});
