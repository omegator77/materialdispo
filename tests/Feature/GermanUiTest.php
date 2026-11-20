<?php

use App\Models\User;

/**
 * Die App ist durchgehend deutsch: Auch Anmeldung, Profil und Validierungsmeldungen
 * (Breeze liefert sie sonst englisch) müssen deutsch sein, unabhängig von APP_LOCALE.
 */
test('die Anmeldeseite ist deutsch', function () {
    $this->get('/login')->assertOk()
        ->assertSee('Anmelden')->assertSee('E-Mail')->assertSee('Passwort')
        ->assertDontSee('Log in')->assertDontSee('Forgot your password');
});

test('Validierungsmeldungen sind deutsch', function () {
    $this->post('/login', [])->assertSessionHasErrors(['email', 'password']);

    $meldung = session('errors')->first('email');

    expect($meldung)->toContain('erforderlich')->not->toContain('required');
});

test('falsche Zugangsdaten melden deutsch', function () {
    User::factory()->create(['email' => 'a@example.test']);

    $this->post('/login', ['email' => 'a@example.test', 'password' => 'falsch']);

    expect(session('errors')->first('email'))->toContain('Zugangsdaten');
});

test('das Profil ist deutsch', function () {
    $this->actingAs(User::factory()->create(['role' => 'user']))->get('/profile')->assertOk()
        ->assertSee('Konto löschen')->assertDontSee('Delete Account')->assertDontSee('Profile Information');
});

test('Pflichtfeld-Meldung nennt das Feld mit deutschem Namen', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post(route('suppliers.store'), [])->assertSessionHasErrors();

    expect(collect(session('errors')->all())->implode(' '))->not->toContain('field is required');
});
