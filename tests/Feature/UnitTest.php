<?php

use App\Models\Unit;
use App\Models\User;

test('admin can create, view, list, update and delete a unit', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post(route('units.store'), [
        'bezeichnung' => 'Kameras',
        'description' => 'Kameragehäuse',
    ])->assertRedirect(route('units.index'));

    $unit = Unit::where('bezeichnung', 'Kameras')->firstOrFail();
    expect($unit->description)->toBe('Kameragehäuse');

    $this->actingAs($admin)->get(route('units.index'))
        ->assertOk()
        ->assertSee('Kameras');

    $this->actingAs($admin)->get(route('units.show', $unit))
        ->assertOk()
        ->assertSee('Kameras');

    $this->actingAs($admin)->get(route('units.edit', $unit))
        ->assertOk()
        ->assertSee('Kameras');

    $this->actingAs($admin)->put(route('units.update', $unit), [
        'bezeichnung' => 'Kameras (aktualisiert)',
        'description' => 'Neue Beschreibung',
    ])->assertRedirect(route('units.index'));

    expect($unit->fresh()->bezeichnung)->toBe('Kameras (aktualisiert)');

    $this->actingAs($admin)->delete(route('units.destroy', $unit))
        ->assertRedirect(route('units.index'));

    expect(Unit::find($unit->id))->toBeNull();
});

test('creating a unit without a bezeichnung fails validation', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post(route('units.store'), [
        'description' => 'Ohne Bezeichnung',
    ])->assertSessionHasErrors('bezeichnung');

    expect(Unit::count())->toBe(0);
});

test('viewer can list and view units but cannot create, update or delete', function () {
    $viewer = User::factory()->create(['role' => 'viewer']);
    $unit = Unit::create(['bezeichnung' => 'Objektive']);

    $this->actingAs($viewer)->get(route('units.index'))->assertOk();
    $this->actingAs($viewer)->get(route('units.show', $unit))->assertOk();

    $this->actingAs($viewer)->post(route('units.store'), ['bezeichnung' => 'Neu'])->assertForbidden();
    $this->actingAs($viewer)->put(route('units.update', $unit), ['bezeichnung' => 'Neu'])->assertForbidden();
    $this->actingAs($viewer)->delete(route('units.destroy', $unit))->assertForbidden();
});
