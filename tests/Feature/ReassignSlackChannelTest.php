<?php

use App\Models\Item;
use App\Models\Mieter;
use App\Models\Setting;
use App\Models\Unit;
use App\Models\Vermietvorgang;
use Illuminate\Support\Facades\Http;

test('slack:reassign-channel setzt den Kanal zurück und postet neu', function () {
    config(['services.slack.bot_token' => 'xoxb-fake']);
    Setting::set('slack_reminder_channel', 'rental');

    $unit = Unit::create(['bezeichnung' => 'Stück']);
    $item = Item::create(['bezeichnung' => 'Kamera X', 'units_id' => $unit->id]);
    $mieter = Mieter::create(['bezeichnung' => 'Testkunde']);

    $vermietvorgang = Vermietvorgang::create([
        'bezeichnung' => 'mietmalfix - die Maschinenvermieter V-260703',
        'mieter_id' => $mieter->id,
        'rent_start' => '2026-08-01',
        'rent_end' => '2026-08-05',
        'slack_channel' => 'C_TESTKANAL',
        'slack_message_ts' => '111.222',
    ]);
    $vermietvorgang->items()->attach($item->id);

    Http::fake([
        'https://slack.com/api/chat.postMessage' => Http::response(['ok' => true, 'channel' => 'C_RENTAL', 'ts' => '999.888']),
    ]);

    $this->artisan('slack:reassign-channel', ['type' => 'vermietvorgang', 'search' => 'V-260703'])
        ->assertExitCode(0);

    $vermietvorgang->refresh();

    expect($vermietvorgang->slack_channel)->toBe('C_RENTAL');
    expect($vermietvorgang->slack_message_ts)->toBe('999.888');

    Http::assertSent(fn ($request) => $request->url() === 'https://slack.com/api/chat.postMessage'
        && $request['channel'] === 'rental');
});

test('slack:reassign-channel bricht bei mehrdeutiger Suche ab', function () {
    config(['services.slack.bot_token' => 'xoxb-fake']);

    $unit = Unit::create(['bezeichnung' => 'Stück']);
    $item = Item::create(['bezeichnung' => 'Kamera X', 'units_id' => $unit->id]);
    $mieter = Mieter::create(['bezeichnung' => 'Testkunde']);

    foreach (['V-260702', 'V-260703'] as $ref) {
        $v = Vermietvorgang::create([
            'bezeichnung' => "MALSEHEN {$ref}",
            'mieter_id' => $mieter->id,
            'rent_start' => '2026-08-01',
            'rent_end' => '2026-08-05',
            'slack_channel' => 'C_TESTKANAL',
            'slack_message_ts' => '111.222',
        ]);
        $v->items()->attach($item->id);
    }

    Http::fake();

    $this->artisan('slack:reassign-channel', ['type' => 'vermietvorgang', 'search' => 'MALSEHEN'])
        ->assertExitCode(1);

    Http::assertNothingSent();
});
