<?php

use App\Models\Unit;
use App\Models\User;

test('reorder swaps sort_order with the neighbor when moving up or down', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $first = Unit::create(['bezeichnung' => 'Kameras']);
    $second = Unit::create(['bezeichnung' => 'Objektive']);
    $third = Unit::create(['bezeichnung' => 'Stative']);

    expect($first->sort_order)->toBeLessThan($second->sort_order);
    expect($second->sort_order)->toBeLessThan($third->sort_order);

    $secondSortOrder = $second->sort_order;
    $thirdSortOrder = $third->sort_order;

    $this->actingAs($admin)
        ->post(route('units.reorder', $second), ['direction' => 'down'])
        ->assertRedirect(route('units.index'));

    expect($second->fresh()->sort_order)->toBe($thirdSortOrder);
    expect($third->fresh()->sort_order)->toBe($secondSortOrder);
});

test('moving the first unit up is a no-op that does not error', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $first = Unit::create(['bezeichnung' => 'Kameras']);
    Unit::create(['bezeichnung' => 'Objektive']);

    $originalSortOrder = $first->sort_order;

    $this->actingAs($admin)
        ->post(route('units.reorder', $first), ['direction' => 'up'])
        ->assertRedirect(route('units.index'));

    expect($first->fresh()->sort_order)->toBe($originalSortOrder);
});

test('moving the last unit down is a no-op that does not error', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Unit::create(['bezeichnung' => 'Kameras']);
    $last = Unit::create(['bezeichnung' => 'Objektive']);

    $originalSortOrder = $last->sort_order;

    $this->actingAs($admin)
        ->post(route('units.reorder', $last), ['direction' => 'down'])
        ->assertRedirect(route('units.index'));

    expect($last->fresh()->sort_order)->toBe($originalSortOrder);
});

test('reorder requires a valid direction', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $unit = Unit::create(['bezeichnung' => 'Kameras']);

    $this->actingAs($admin)
        ->post(route('units.reorder', $unit), ['direction' => 'sideways'])
        ->assertSessionHasErrors('direction');
});
