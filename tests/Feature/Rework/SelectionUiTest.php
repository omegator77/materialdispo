<?php

use App\Models\Item;
use App\Models\Production;
use App\Models\Unit;
use App\Models\User;

beforeEach(function () {
    // Die Geräteliste wählt ihre Tabellenvariante an festen Gruppen-IDs (1 = Kameras).
    $this->unit = Unit::forceCreate(['id' => 1, 'bezeichnung' => 'Kameras']);
    $this->item = Item::create(['units_id' => $this->unit->id, 'bezeichnung' => 'LDX', 'nummer' => '1']);
    Production::create(['bezeichnung' => 'Derby', 'booking_start' => now()->addDays(2)->toDateString(), 'booking_end' => now()->addDays(3)->toDateString()]);
});

test('timeline offers per-device and per-group selection plus the bulk bar to users', function () {
    $html = $this->actingAs(User::factory()->create(['role' => 'user']))->get(route('timeline.items'))->assertOk()->getContent();

    expect($html)->toContain('bulk-select')
        ->and($html)->toContain('bulk-select-group')
        ->and($html)->toContain(route('items.bulk.action'))
        ->and($html)->toContain('Derby');
});

test('timeline selection is hidden from viewers', function () {
    $html = $this->actingAs(User::factory()->create(['role' => 'viewer']))->get(route('timeline.items'))->assertOk()->getContent();

    expect($html)->not->toContain('bulk-select')->and($html)->not->toContain(route('items.bulk.action'));
});

test('every device list variant has selectable mobile cards', function () {
    $user = User::factory()->create(['role' => 'user']);
    foreach (['', '?unit_id=1', '?unit_id=2', '?unit_id=9'] as $query) {
        $html = $this->actingAs($user)->get(route('items.index').$query)->assertOk()->getContent();
        // je Gerät: eine Checkbox in der Tabelle + eine in der Mobil-Karte
        $expected = str_contains($query, 'unit_id=1') || $query === '' ? 2 : 0;
        if ($expected) {
            expect(substr_count($html, 'class="bulk-select '))->toBe($expected);
        }
    }
});

test('bulk assignment works with a return to the timeline', function () {
    $user = User::factory()->create(['role' => 'user']);
    $prod = Production::first();

    $this->actingAs($user)->from(route('timeline.items'))
        ->post(route('items.bulk.action'), ['action' => 'production', 'target_id' => $prod->id, 'item_ids' => [$this->item->id]])
        ->assertRedirect(route('timeline.items'));

    expect($prod->items()->count())->toBe(1);
});
