<?php

use App\Models\Production;
use App\Models\User;

function produktionsDaten(array $override = []): array
{
    return array_merge([
        'bezeichnung' => 'Sommerfest',
        'booking_start' => '10.12.2026',
    ], $override);
}

test('anlegen leitet direkt zur neuen Produktion', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->post('/productions', produktionsDaten());

    $p = Production::where('bezeichnung', 'Sommerfest')->firstOrFail();
    $response->assertRedirect(route('productions.show', $p));
    expect(substr((string) $p->booking_end, 0, 10))->toBe('2026-12-10');
});

test('Ende vor Beginn bleibt ein Fehler', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post('/productions', produktionsDaten(['booking_end' => '09.12.2026']))
        ->assertSessionHasErrors('booking_end');
});

test('Beginn bleibt Pflicht', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post('/productions', ['bezeichnung' => 'X'])
        ->assertSessionHasErrors('booking_start');
});

test('gelöschte Vorlage führt trotzdem zur neuen Produktion', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->post('/productions', produktionsDaten(['from_production_id' => 99999]));

    $p = Production::where('bezeichnung', 'Sommerfest')->firstOrFail();
    $response->assertRedirect(route('productions.show', $p));
});

test('Schnelleingabe ist nur direkt nach dem Anlegen fokussiert', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $p = Production::create(['bezeichnung' => 'P', 'booking_start' => '2026-12-10', 'booking_end' => '2026-12-10']);

    $this->actingAs($admin)->get(route('productions.show', $p))->assertDontSee('autofocus', false);

    $this->withSession(['focus_quick_add' => true])
        ->get(route('productions.show', $p))->assertSee('autofocus', false);
});

test('update mit leerem Ende setzt Ende auf Beginn', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $p = Production::create(['bezeichnung' => 'P', 'booking_start' => '2026-12-01', 'booking_end' => '2026-12-05']);

    $this->actingAs($admin)->put(route('productions.update', $p), [
        'bezeichnung' => 'P', 'booking_start' => '03.12.2026', 'booking_end' => '',
    ])->assertSessionHasNoErrors();

    expect(substr((string) $p->fresh()->booking_end, 0, 10))->toBe('2026-12-03');
});
