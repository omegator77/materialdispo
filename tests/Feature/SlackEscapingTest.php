<?php

use App\Models\Item;
use App\Models\Mietvorgang;
use App\Models\Production;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\VbProtokoll;
use App\Services\SlackVorgangSync;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'services.slack.bot_token' => 'xoxb-test',
        'services.slack.production_channel' => 'C100',
        'services.slack.reminder_channel' => 'C200',
    ]);
    Http::fake(['slack.com/api/chat.postMessage' => Http::response(['ok' => true, 'channel' => 'C100', 'ts' => '1.1'])]);
});

function slackPostedBlocks(): array
{
    $posted = null;
    Http::assertSent(function ($request) use (&$posted) {
        $posted = $request;

        return str_contains($request->url(), 'chat.postMessage');
    });

    return $posted->data();
}

test('Slack-Steuerzeichen in Produktionsdaten werden maskiert, eigene Links bleiben', function () {
    $production = Production::create([
        'bezeichnung' => 'Show <!channel> & <https://evil.example|Klick>',
        'booking_start' => '2026-08-01',
        'booking_end' => '2026-08-02',
    ]);
    VbProtokoll::forceCreate(['production_id' => $production->id, 'kunde' => '<!here> Kunde', 'produktionsort' => 'Halle <!everyone>']);

    app(SlackVorgangSync::class)->syncProduction($production->fresh());

    $data = slackPostedBlocks();
    $text = $data['blocks'][0]['text']['text'];

    expect($text)->not->toContain('<!channel>')->not->toContain('<!here>')->not->toContain('<!everyone>')
        ->not->toContain('<https://evil.example')
        ->toContain('&lt;!channel&gt; &amp; &lt;https://evil.example|Klick&gt;')
        ->toContain('&lt;!here&gt; Kunde')
        ->toContain('<'.route('productions.show', $production).'|Zur Produktion in der App>')
        ->and($data['text'])->not->toContain('<!channel>');
});

test('Slack-Steuerzeichen in Mietvorgang-Daten und Bestätigernamen werden maskiert', function () {
    $supplier = Supplier::create(['bezeichnung' => '<!channel> Verleih']);
    $unit = Unit::create(['bezeichnung' => 'Kameras']);
    $item = Item::create(['units_id' => $unit->id, 'bezeichnung' => 'Kamera <!here>']);
    $user = User::factory()->create(['name' => 'Max <!everyone>']);

    $mv = Mietvorgang::create([
        'suppliers_id' => $supplier->id,
        'rent_start' => '2026-08-01',
        'rent_end' => '2026-08-05',
        'bezeichnung' => 'Miete <!channel>',
        'transport_type_start' => 'Abholung <!here>',
    ]);
    $mv->items()->attach($item->id);
    $mv->forceFill(['transport_start_confirmed_at' => now(), 'transport_start_confirmed_by' => $user->id])->save();

    app(SlackVorgangSync::class)->syncMietvorgang($mv->fresh());

    $data = slackPostedBlocks();
    $info = $data['blocks'][0]['text']['text'];
    $status = $data['blocks'][1]['text']['text'];

    expect($info)->not->toMatch('/<!(channel|here|everyone)>/')
        ->toContain('&lt;!channel&gt; Verleih')->toContain('Kamera &lt;!here&gt;')->toContain('Abholung &lt;!here&gt;')
        ->and($status)->not->toMatch('/<!(channel|here|everyone)>/')->toContain('Max &lt;!everyone&gt;')
        ->and($data['text'])->not->toContain('<!channel>');
});

test('escape maskiert nur &, < und >', function () {
    expect(SlackVorgangSync::escape('<!channel> & "x" \'y\''))->toBe('&lt;!channel&gt; &amp; "x" \'y\'')
        ->and(SlackVorgangSync::escape(null))->toBe('');
});
