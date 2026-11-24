<?php

use App\Models\Setting;
use App\Models\User;

test('Admin speichert den Bestell-Kanal und sieht ihn wieder', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->put(route('settings.update'), ['slack_order_channel' => '#bestellungen'])->assertRedirect(route('settings.edit'));

    expect(Setting::get('slack_order_channel'))->toBe('#bestellungen');
    $this->actingAs($admin)->get(route('settings.edit'))->assertOk()->assertSee('value="#bestellungen"', false);
});
