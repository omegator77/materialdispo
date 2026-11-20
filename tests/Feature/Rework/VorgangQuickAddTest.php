<?php

use App\Models\Geraetetyp;
use App\Models\Item;
use App\Models\Mieter;
use App\Models\Mietvorgang;
use App\Models\Production;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Vermietvorgang;

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'user']);
    $this->unit = Unit::create(['bezeichnung' => 'Kameras']);
    $this->typ = Geraetetyp::create(['units_id' => $this->unit->id, 'bezeichnung' => 'LDX']);
    $this->mk = fn ($nr, $extra = []) => Item::create(array_merge(['units_id' => $this->unit->id, 'geraetetyp_id' => $this->typ->id, 'bezeichnung' => 'LDX', 'nummer' => (string) $nr], $extra));
});

test('quick add on a Mietvorgang only considers items of that supplier', function () {
    $s1 = Supplier::create(['bezeichnung' => 'Pixmade']);
    $s2 = Supplier::create(['bezeichnung' => 'Andere']);
    $mine = ($this->mk)(1, ['suppliers_id' => $s1->id]);
    ($this->mk)(2, ['suppliers_id' => $s2->id]);
    ($this->mk)(3);
    $mv = Mietvorgang::create(['suppliers_id' => $s1->id, 'rent_start' => '2026-12-01', 'rent_end' => '2026-12-05', 'bezeichnung' => 'M']);

    $this->actingAs($this->user)->postJson(route('mietvorgaenge.quickAdd.preview', $mv), ['text' => '3 LDX'])
        ->assertJsonPath('total', 1)->assertJsonPath('missing', 2);

    $this->actingAs($this->user)->post(route('mietvorgaenge.quickAdd', $mv), ['text' => '3 LDX'])
        ->assertRedirect(route('mietvorgaenge.show', $mv));

    expect($mv->items()->pluck('items.id')->all())->toBe([$mine->id]);
});

test('quick add on a Vermietvorgang skips items planned in overlapping productions', function () {
    $mieter = Mieter::create(['bezeichnung' => 'Kunde']);
    $busy = ($this->mk)(1);
    $free = ($this->mk)(2);
    Production::create(['bezeichnung' => 'P', 'booking_start' => '2026-12-02', 'booking_end' => '2026-12-03'])->items()->attach($busy->id);
    $vv = Vermietvorgang::create(['mieter_id' => $mieter->id, 'rent_start' => '2026-12-01', 'rent_end' => '2026-12-05', 'bezeichnung' => 'V']);

    $json = $this->actingAs($this->user)->postJson(route('vermietvorgaenge.quickAdd.preview', $vv), ['text' => '2 LDX'])
        ->assertJsonPath('total', 1)->json();
    expect($json['lines'][0]['blocked'][0]['id'])->toBe($busy->id);

    $this->actingAs($this->user)->post(route('vermietvorgaenge.quickAdd', $vv), ['text' => '2 LDX']);
    expect($vv->items()->pluck('items.id')->all())->toBe([$free->id]);
});

test('viewers cannot quick add to vorgaenge', function () {
    $viewer = User::factory()->create(['role' => 'viewer']);
    $s = Supplier::create(['bezeichnung' => 'x']);
    $mv = Mietvorgang::create(['suppliers_id' => $s->id, 'rent_start' => '2026-12-01', 'rent_end' => '2026-12-05', 'bezeichnung' => 'M']);

    $this->actingAs($viewer)->post(route('mietvorgaenge.quickAdd', $mv), ['text' => 'x'])->assertForbidden();
});
