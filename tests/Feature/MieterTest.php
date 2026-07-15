<?php

use App\Models\Mieter;
use App\Models\User;
use App\Models\Vermietvorgang;

test('deleting a mieter that still has vermietvorgaenge is blocked', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $mieter = Mieter::create(['bezeichnung' => 'Kunde X']);
    Vermietvorgang::create(['mieter_id' => $mieter->id, 'rent_start' => '2026-10-12', 'rent_end' => '2026-10-14']);

    $response = $this->actingAs($admin)->delete(route('mieter.destroy', $mieter));

    $response->assertRedirect(route('mieter.index'));
    $response->assertSessionHas('error');
    expect(Mieter::find($mieter->id))->not->toBeNull();
});

test('a mieter without vermietvorgaenge can be deleted', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $mieter = Mieter::create(['bezeichnung' => 'Kunde ohne Vorgang']);

    $this->actingAs($admin)->delete(route('mieter.destroy', $mieter))
        ->assertRedirect(route('mieter.index'));

    expect(Mieter::find($mieter->id))->toBeNull();
});
