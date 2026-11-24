<?php

use App\Models\User;

test('Benutzerformulare enthalten die Buchhaltungs-Checkbox', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $other = User::factory()->create();

    $this->actingAs($admin)->get(route('users.create'))
        ->assertOk()->assertSee('name="ist_buchhaltung"', false)->assertSee('form-check', false);
    $this->actingAs($admin)->get(route('users.edit', $other))
        ->assertOk()->assertSee('name="ist_buchhaltung"', false)->assertSee('form-check', false);
});

test('Standard ist false', function () {
    expect(User::factory()->create()->fresh()->ist_buchhaltung)->toBeFalse();
});

test('Anlegen und Ändern setzt und entfernt das Flag', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post(route('users.store'), [
        'name' => 'Bea Buch', 'email' => 'bea@example.com', 'role' => 'user',
        'password' => 'passwort123', 'password_confirmation' => 'passwort123',
        'ist_buchhaltung' => '1',
    ])->assertRedirect(route('users.index'));
    $bea = User::where('email', 'bea@example.com')->firstOrFail();
    expect($bea->ist_buchhaltung)->toBeTrue();
    expect(User::buchhaltung()->pluck('id')->all())->toBe([$bea->id]);

    $this->actingAs($admin)->put(route('users.update', $bea), [
        'name' => 'Bea Buch', 'email' => 'bea@example.com', 'role' => 'user',
    ])->assertRedirect(route('users.index'));
    expect($bea->fresh()->ist_buchhaltung)->toBeFalse();

    $this->actingAs($admin)->put(route('users.update', $bea), [
        'name' => 'Bea Buch', 'email' => 'bea@example.com', 'role' => 'user', 'ist_buchhaltung' => '1',
    ]);
    expect($bea->fresh()->ist_buchhaltung)->toBeTrue();
});

test('Nicht-Admin erhält 403 auf Benutzerseiten', function () {
    $user = User::factory()->create(['role' => 'user']);

    $this->actingAs($user)->get(route('users.create'))->assertForbidden();
    $this->actingAs($user)->get(route('users.edit', $user))->assertForbidden();
});
