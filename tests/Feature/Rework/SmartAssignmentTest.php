<?php

use App\Models\Geraetetyp;
use App\Models\Item;
use App\Models\Production;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\VbProtokoll;
use App\Services\SmartAssignmentService;

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'user']);
    $this->unit = Unit::create(['bezeichnung' => 'Stativ']);
    $this->typ = Geraetetyp::create(['units_id' => $this->unit->id, 'bezeichnung' => 'Sachtler Ace']);
    $this->other = Geraetetyp::create(['units_id' => $this->unit->id, 'bezeichnung' => 'Vinten Vision']);

    $this->prod = Production::create(['bezeichnung' => 'Derby', 'booking_start' => '2026-12-05', 'booking_end' => '2026-12-08']);
    $this->earlier = Production::create(['bezeichnung' => 'Pokal', 'booking_start' => '2026-12-06', 'booking_end' => '2026-12-07']);

    $mk = fn ($typ, $nr, $extra = []) => Item::create(array_merge([
        'units_id' => $this->unit->id, 'geraetetyp_id' => $typ->id, 'bezeichnung' => $typ->bezeichnung, 'nummer' => $nr,
    ], $extra));
    $this->mk = $mk;
});

test('quick add picks free devices, prefers own over rented, lowest number first', function () {
    $supplier = Supplier::create(['bezeichnung' => 'Pixmade']);
    $rented = ($this->mk)($this->typ, '1', ['suppliers_id' => $supplier->id]);
    $own10 = ($this->mk)($this->typ, '10');
    $own2 = ($this->mk)($this->typ, '2');

    $this->actingAs($this->user)
        ->post(route('productions.quickAdd', $this->prod), ['text' => '2x Sachtler'])
        ->assertRedirect(route('productions.show', $this->prod));

    expect($this->prod->items()->pluck('items.id')->all())->toEqualCanonicalizing([$own2->id, $own10->id])
        ->and($this->prod->items()->where('items.id', $rented->id)->exists())->toBeFalse();
});

test('quick add skips devices blocked by overlapping productions', function () {
    $busy = ($this->mk)($this->typ, '1');
    $free = ($this->mk)($this->typ, '2');
    $this->earlier->items()->attach($busy->id);

    $response = $this->actingAs($this->user)
        ->postJson(route('productions.quickAdd.preview', $this->prod), ['text' => '2 Sachtler'])
        ->assertOk();

    $response->assertJsonPath('total', 1)->assertJsonPath('missing', 1)
        ->assertJsonPath('lines.0.items.0.id', $free->id)
        ->assertJsonPath('lines.0.blocked.0.id', $busy->id);
    expect($response->json('lines.0.blocked.0.reason'))->toContain('Pokal');
});

test('shortage proposes substitutes of another type in the same group', function () {
    $free = ($this->mk)($this->typ, '1');
    $substitute = ($this->mk)($this->other, '9');

    $this->actingAs($this->user)
        ->postJson(route('productions.quickAdd.preview', $this->prod), ['text' => '2 Sachtler'])
        ->assertJsonPath('lines.0.missing', 1)
        ->assertJsonPath('lines.0.substitutes.0.id', $substitute->id);
});

test('preview does not write, unknown text is reported', function () {
    ($this->mk)($this->typ, '1');

    $this->actingAs($this->user)
        ->postJson(route('productions.quickAdd.preview', $this->prod), ['text' => '1 Sachtler, 4 Kaffeemaschine'])
        ->assertJsonPath('lines.1.matched', false)
        ->assertJsonPath('missing', 4);

    expect($this->prod->items()->count())->toBe(0);
});

test('one device is never picked twice across lines', function () {
    ($this->mk)($this->typ, '1');

    $this->actingAs($this->user)
        ->postJson(route('productions.quickAdd.preview', $this->prod), ['text' => '1 Sachtler, 1 Stativ'])
        ->assertJsonPath('total', 1)->assertJsonPath('missing', 1);
});

test('auto-fill covers open VB requirements and ignores met ones', function () {
    $a = ($this->mk)($this->typ, '1');
    $b = ($this->mk)($this->typ, '2');
    ($this->mk)($this->typ, '3');
    $this->prod->items()->attach($a->id);

    $vb = VbProtokoll::create(['production_id' => $this->prod->id, 'kunde' => 'X']);
    $vb->anforderungen()->create(['geraetetyp_id' => $this->typ->id, 'unit_id' => $this->unit->id, 'anzahl' => 3]);

    $this->actingAs($this->user)->post(route('productions.autoFill', $this->prod))->assertSessionHas('success');

    expect($this->prod->items()->count())->toBe(3);

    // zweiter Aufruf ist ein No-op
    $this->actingAs($this->user)->post(route('productions.autoFill', $this->prod));
    expect($this->prod->items()->count())->toBe(3);
});

test('replacing a conflicting device swaps in a free one of the same type', function () {
    $bad = ($this->mk)($this->typ, '1');
    $good = ($this->mk)($this->typ, '2');
    ($this->mk)($this->other, '3');
    $this->prod->items()->attach($bad->id, ['notes' => 'Tonwagen']);
    $this->earlier->items()->attach($bad->id);

    $this->actingAs($this->user)
        ->post(route('productions.replaceItem', [$this->prod, $bad]))
        ->assertSessionHas('success');

    $items = $this->prod->items()->get();
    expect($items->pluck('id')->all())->toBe([$good->id])
        ->and($items->first()->pivot->notes)->toBe('Tonwagen');
});

test('replace reports failure when nothing is free', function () {
    $bad = ($this->mk)($this->typ, '1');
    $this->prod->items()->attach($bad->id);
    $this->earlier->items()->attach($bad->id);

    $this->actingAs($this->user)
        ->post(route('productions.replaceItem', [$this->prod, $bad]))
        ->assertSessionHas('error');

    expect($this->prod->items()->pluck('items.id')->all())->toBe([$bad->id]);
});

test('suggestions come from past co-bookings and exclude unavailable devices', function () {
    $core = ($this->mk)($this->typ, '1');
    $partner = ($this->mk)($this->other, '2');
    $blocked = ($this->mk)($this->other, '3');

    $past1 = Production::create(['bezeichnung' => 'Alt1', 'booking_start' => '2026-01-01', 'booking_end' => '2026-01-02']);
    $past2 = Production::create(['bezeichnung' => 'Alt2', 'booking_start' => '2026-02-01', 'booking_end' => '2026-02-02']);
    foreach ([$past1, $past2] as $p) {
        $p->items()->attach([$core->id, $partner->id, $blocked->id]);
    }
    $this->prod->items()->attach($core->id);
    $this->earlier->items()->attach($blocked->id);

    $suggestions = app(SmartAssignmentService::class)->suggestionsFor($this->prod);

    expect(collect($suggestions)->pluck('item.id')->all())->toBe([$partner->id])
        ->and($suggestions[0]['together'])->toBe(2);
});

test('similar production is found by name', function () {
    $old = Production::create(['bezeichnung' => 'Derby Hamburg 2025', 'booking_start' => '2025-12-05', 'booking_end' => '2025-12-08']);
    $old->items()->attach(($this->mk)($this->typ, '1')->id);
    $this->prod->update(['bezeichnung' => 'Derby Hamburg 2026']);

    expect(app(SmartAssignmentService::class)->similarProduction($this->prod->fresh())?->id)->toBe($old->id);
});

test('viewers cannot use smart endpoints', function () {
    $viewer = User::factory()->create(['role' => 'viewer']);

    $this->actingAs($viewer)->post(route('productions.quickAdd', $this->prod), ['text' => 'x'])->assertForbidden();
    $this->actingAs($viewer)->post(route('productions.autoFill', $this->prod))->assertForbidden();
});
