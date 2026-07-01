<?php

use App\Models\Item;
use App\Models\Production;
use App\Models\Unit;
use App\Models\User;
use App\Models\VbProtokoll;

function productionWithPacklistItem(array $overrides = []): Production
{
    $unit = Unit::create(['bezeichnung' => 'Kameras']);
    $item = Item::create(['units_id' => $unit->id, 'bezeichnung' => 'Testgerät']);

    $production = Production::create(array_merge([
        'bezeichnung' => 'Testproduktion',
        'booking_start' => '2026-10-01',
        'booking_end' => '2026-10-05',
    ], $overrides));

    $production->items()->attach($item->id);

    return $production;
}

test('packlist tile shows the abgleich report as disabled until packvorgang is confirmed', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $production = productionWithPacklistItem();

    VbProtokoll::create([
        'production_id' => $production->id,
        'created_by' => $admin->id,
    ]);

    $response = $this->actingAs($admin)->get(route('itemprods'));

    $response->assertOk()
        ->assertSee('Abgleich-Report')
        ->assertDontSee(route('vb-protokoll.pdf-abgleich', $production->id));
});

test('packlist tile links to the abgleich report once packvorgang is confirmed', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $production = productionWithPacklistItem([
        'packvorgang_confirmed_at' => now(),
        'packvorgang_confirmed_by' => $admin->id,
    ]);

    VbProtokoll::create([
        'production_id' => $production->id,
        'created_by' => $admin->id,
    ]);

    $response = $this->actingAs($admin)->get(route('itemprods'));

    $response->assertOk()
        ->assertSee(route('vb-protokoll.pdf-abgleich', $production->id));
});

test('packlist tile has no abgleich report button when there is no vb-protokoll', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    productionWithPacklistItem();

    $response = $this->actingAs($admin)->get(route('itemprods'));

    $response->assertOk()->assertDontSee('Abgleich-Report');
});

test('production detail page no longer has the abgleich report button', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $production = productionWithPacklistItem();

    VbProtokoll::create([
        'production_id' => $production->id,
        'created_by' => $admin->id,
    ]);

    $response = $this->actingAs($admin)->get(route('productions.show', $production));

    $response->assertOk()->assertDontSee('Abgleich-Report');
});
