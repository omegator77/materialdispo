<?php

use App\Models\Supplier;
use App\Models\User;

test('admin can create, view, list, update and delete a supplier', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post(route('suppliers.store'), [
        'bezeichnung' => 'Mietpartner GmbH',
        'kontakt' => 'Max Mustermann',
        'phone' => '0123456789',
        'email' => 'kontakt@mietpartner.de',
    ])->assertRedirect(route('suppliers.index'));

    $supplier = Supplier::where('bezeichnung', 'Mietpartner GmbH')->firstOrFail();
    expect($supplier->email)->toBe('kontakt@mietpartner.de');

    $this->actingAs($admin)->get(route('suppliers.index'))->assertOk()->assertSee('Mietpartner GmbH');
    $this->actingAs($admin)->get(route('suppliers.show', $supplier))->assertOk()->assertSee('Mietpartner GmbH');
    $this->actingAs($admin)->get(route('suppliers.edit', $supplier))->assertOk();

    $this->actingAs($admin)->put(route('suppliers.update', $supplier), [
        'bezeichnung' => 'Mietpartner GmbH (neu)',
        'email' => 'neu@mietpartner.de',
    ])->assertRedirect(route('suppliers.index'));

    expect($supplier->fresh()->bezeichnung)->toBe('Mietpartner GmbH (neu)');

    $this->actingAs($admin)->delete(route('suppliers.destroy', $supplier))
        ->assertRedirect(route('suppliers.index'));

    expect(Supplier::find($supplier->id))->toBeNull();
});

test('creating a supplier without bezeichnung or with an invalid email fails validation', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post(route('suppliers.store'), [
        'email' => 'not-an-email',
    ])->assertSessionHasErrors(['bezeichnung', 'email']);

    expect(Supplier::count())->toBe(0);
});

test('viewer cannot create, update or delete a supplier', function () {
    $viewer = User::factory()->create(['role' => 'viewer']);
    $supplier = Supplier::create([
        'bezeichnung' => 'Mietpartner',
        'kontakt' => 'Max Mustermann',
        'phone' => '0123456789',
    ]);

    $this->actingAs($viewer)->post(route('suppliers.store'), ['bezeichnung' => 'Neu'])->assertForbidden();
    $this->actingAs($viewer)->put(route('suppliers.update', $supplier), ['bezeichnung' => 'Neu'])->assertForbidden();
    $this->actingAs($viewer)->delete(route('suppliers.destroy', $supplier))->assertForbidden();
});
