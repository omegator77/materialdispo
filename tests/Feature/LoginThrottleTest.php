<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::get('/__ip', fn () => request()->ip());
});

test('X-Forwarded-For von einer öffentlichen Adresse wird ignoriert', function () {
    $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])
        ->withHeader('X-Forwarded-For', '1.2.3.4')
        ->get('/__ip')
        ->assertSee('8.8.8.8');
});

test('X-Forwarded-For eines Reverse-Proxys im privaten Netz wird weiter beachtet', function () {
    $this->withServerVariables(['REMOTE_ADDR' => '172.18.0.2'])
        ->withHeader('X-Forwarded-For', '203.0.113.7')
        ->get('/__ip')
        ->assertSee('203.0.113.7');
});

test('Login wird pro E-Mail gedrosselt, auch wenn die Absender-IP rotiert', function () {
    $user = User::factory()->create(['email' => 'opfer@example.com']);
    RateLimiter::clear('login-email|opfer@example.com');

    $erreicht = null;
    foreach (range(1, 25) as $i) {
        $response = $this->withHeader('X-Forwarded-For', "198.51.100.{$i}")
            ->post('/login', ['email' => $user->email, 'password' => 'falsch']);

        if (session('errors')->first('email') !== trans('auth.failed')) {
            $erreicht = $i;
            break;
        }
    }

    expect($erreicht)->not->toBeNull()->toBeLessThanOrEqual(21);
    $this->assertGuest();
});

test('Das Limit pro E-Mail und IP (5 Versuche) bleibt bestehen', function () {
    $user = User::factory()->create(['email' => 'user@example.com']);

    foreach (range(1, 5) as $i) {
        $this->post('/login', ['email' => $user->email, 'password' => 'falsch']);
    }

    $this->post('/login', ['email' => $user->email, 'password' => 'falsch']);

    expect(session('errors')->first('email'))->not->toBe(trans('auth.failed'));
});

test('Erfolgreicher Login setzt beide Zähler zurück', function () {
    $user = User::factory()->create(['email' => 'ok@example.com']);

    foreach (range(1, 3) as $i) {
        $this->post('/login', ['email' => $user->email, 'password' => 'falsch']);
    }
    $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect();
    $this->post('/logout');

    foreach (range(1, 4) as $i) {
        $this->post('/login', ['email' => $user->email, 'password' => 'falsch']);
        expect(session('errors')->first('email'))->toBe(trans('auth.failed'));
    }
});
