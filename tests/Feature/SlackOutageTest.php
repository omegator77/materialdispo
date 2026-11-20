<?php

use App\Models\Item;
use App\Models\Mietvorgang;
use App\Models\Production;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Services\ItemAssignmentService;
use App\Services\SlackVorgangSync;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'services.slack.bot_token' => 'xoxb-test',
        'services.slack.production_channel' => 'C100',
        'services.slack.reminder_channel' => 'C200',
    ]);
    Http::fake(fn () => throw new ConnectionException('Slack nicht erreichbar'));
});

test('Ein Slack-Ausfall beim Synchronisieren einer Produktion wirft keine Exception', function () {
    $production = Production::create(['bezeichnung' => 'Prod', 'booking_start' => '2026-08-01', 'booking_end' => '2026-08-02']);

    app(SlackVorgangSync::class)->syncProduction($production);

    expect(true)->toBeTrue();
});

test('Ein Slack-Ausfall verhindert nicht das Zuordnen eines Geräts zu einem Mietvorgang', function () {
    $supplier = Supplier::create(['bezeichnung' => 'Verleih']);
    $item = Item::create(['units_id' => Unit::create(['bezeichnung' => 'K'])->id, 'bezeichnung' => 'Kamera']);
    $mv = Mietvorgang::create(['suppliers_id' => $supplier->id, 'rent_start' => '2026-08-01', 'rent_end' => '2026-08-05']);

    $result = app(ItemAssignmentService::class)->attachToMietvorgang($item, $mv, manual: true);

    expect($result['added'])->toBeTrue()
        ->and($item->mietvorgaenge()->count())->toBe(1);
});

test('Ein Slack-Ausfall beim Thread-Reply und Kompaktieren wirft keine Exception', function () {
    $production = Production::create(['bezeichnung' => 'Prod', 'booking_start' => '2026-08-01', 'booking_end' => '2026-08-02']);
    $production->forceFill([
        'slack_channel' => 'C100', 'slack_message_ts' => '1.1',
        'packvorgang_confirmed_at' => now()->subDays(3),
    ])->save();

    $sync = app(SlackVorgangSync::class);
    $sync->threadReply($production, 'Test');
    $sync->compactIfDue($production);

    expect($production->fresh()->slack_compacted_at)->toBeNull();
});

test('Produktion bearbeiten klappt trotz Slack-Ausfall', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $production = Production::create(['bezeichnung' => 'Prod', 'booking_start' => '2026-08-01', 'booking_end' => '2026-08-02']);

    $this->actingAs($admin)->put(route('productions.update', $production), [
        'bezeichnung' => 'Neu', 'booking_start' => '01.08.2026', 'booking_end' => '03.08.2026',
    ])->assertRedirect();

    expect($production->fresh()->bezeichnung)->toBe('Neu');
});
