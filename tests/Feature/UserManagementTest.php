<?php

use App\Models\User;

test('admin can create, list, update and delete a user', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post(route('users.store'), [
        'name' => 'Neuer Nutzer',
        'email' => 'neu@example.com',
        'role' => 'user',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertRedirect(route('users.index'));

    $user = User::where('email', 'neu@example.com')->firstOrFail();
    expect($user->role)->toBe('user');

    $this->actingAs($admin)->get(route('users.index'))->assertOk()->assertSee('Neuer Nutzer');
    $this->actingAs($admin)->get(route('users.edit', $user))->assertOk();

    $this->actingAs($admin)->put(route('users.update', $user), [
        'name' => 'Neuer Nutzer (aktualisiert)',
        'email' => 'neu@example.com',
        'role' => 'viewer',
    ])->assertRedirect(route('users.index'));

    expect($user->fresh()->name)->toBe('Neuer Nutzer (aktualisiert)')
        ->and($user->fresh()->role)->toBe('viewer');

    $this->actingAs($admin)->delete(route('users.destroy', $user))
        ->assertRedirect(route('users.index'));

    expect(User::find($user->id))->toBeNull();
});

test('creating a user requires a password but updating does not', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $target = User::factory()->create(['email' => 'bestehend@example.com']);

    $this->actingAs($admin)->post(route('users.store'), [
        'name' => 'Ohne Passwort',
        'email' => 'ohnepasswort@example.com',
        'role' => 'user',
    ])->assertSessionHasErrors('password');

    $originalHash = $target->password;

    $this->actingAs($admin)->put(route('users.update', $target), [
        'name' => $target->name,
        'email' => 'bestehend@example.com',
        'role' => 'user',
    ])->assertRedirect(route('users.index'));

    expect($target->fresh()->password)->toBe($originalHash);
});

test('admin cannot delete their own account', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->delete(route('users.destroy', $admin));

    $response->assertRedirect(route('users.index'));
    $response->assertSessionHas('error');
    expect(User::find($admin->id))->not->toBeNull();
});

test('non-admin users cannot access user management', function () {
    $user = User::factory()->create(['role' => 'user']);
    $viewer = User::factory()->create(['role' => 'viewer']);

    $this->actingAs($user)->get(route('users.index'))->assertForbidden();
    $this->actingAs($viewer)->get(route('users.index'))->assertForbidden();
});
