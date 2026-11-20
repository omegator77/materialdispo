<?php

use App\Models\Geraetetyp;
use App\Models\Production;
use App\Models\Unit;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'user']);
    $this->unit = Unit::create(['bezeichnung' => 'Stativ']);
    $this->typ = Geraetetyp::create(['units_id' => $this->unit->id, 'bezeichnung' => 'Sachtler']);
    $this->source = Production::create(['bezeichnung' => 'Derby 2025', 'booking_start' => '2025-12-01', 'booking_end' => '2025-12-02']);
    $this->target = Production::create(['bezeichnung' => 'Derby 2026', 'booking_start' => '2026-12-01', 'booking_end' => '2026-12-02']);

    $vb = $this->source->vbProtokoll()->create(['kunde' => 'Alter Kunde', 'produktionsort' => 'Hamburg', 'crew_kamera' => '4']);
    $vb->anforderungen()->create(['unit_id' => $this->unit->id, 'geraetetyp_id' => $this->typ->id, 'anzahl' => 3, 'notiz' => 'n']);
    $vb->anforderungen()->create(['unit_id' => 1, 'cam_number' => 'Cam 1', 'lens_geraetetyp_id' => $this->typ->id]);
    $vb->freitextBloecke()->create(['ueberschrift' => 'Hinweise', 'text' => 'Regen', 'sort_order' => 1]);
});

test('a protocol can be created from a template, event specific fields stay empty', function () {
    $this->actingAs($this->user)
        ->post(route('vb-protokoll.fromTemplate', [$this->target, $this->source]))
        ->assertRedirect(route('vb-protokoll.edit', $this->target));

    $vb = $this->target->fresh()->vbProtokoll;
    expect($vb)->not->toBeNull()
        ->and($vb->kunde)->toBeNull()
        ->and($vb->produktionsort)->toBeNull()
        ->and($vb->crew_kamera)->toBe('4')
        ->and($vb->created_by)->toBe($this->user->id)
        ->and($vb->anforderungen)->toHaveCount(2)
        ->and($vb->anforderungen->firstWhere('cam_number', 'Cam 1')->lens_geraetetyp_id)->toBe($this->typ->id)
        ->and($vb->anforderungen->firstWhere('anzahl', 3)->notiz)->toBe('n')
        ->and($vb->freitextBloecke->first()->text)->toBe('Regen');
});

test('an existing protocol is never overwritten', function () {
    $this->target->vbProtokoll()->create(['kunde' => 'Behalten']);

    $this->actingAs($this->user)->post(route('vb-protokoll.fromTemplate', [$this->target, $this->source]))
        ->assertRedirect(route('vb-protokoll.edit', $this->target));

    expect($this->target->fresh()->vbProtokoll->kunde)->toBe('Behalten')
        ->and($this->target->fresh()->vbProtokoll->anforderungen)->toHaveCount(0);
});

test('a source without protocol is rejected', function () {
    $empty = Production::create(['bezeichnung' => 'Leer', 'booking_start' => '2025-01-01', 'booking_end' => '2025-01-02']);

    $this->actingAs($this->user)->post(route('vb-protokoll.fromTemplate', [$this->target, $empty]))->assertSessionHas('error');
    expect($this->target->fresh()->vbProtokoll)->toBeNull();
});

test('the create page offers similar productions with a protocol first', function () {
    $other = Production::create(['bezeichnung' => 'Zirkus', 'booking_start' => '2025-05-01', 'booking_end' => '2025-05-02']);
    $other->vbProtokoll()->create(['kunde' => 'x'])->anforderungen()->create(['unit_id' => $this->unit->id, 'anzahl' => 1]);
    $leer = Production::create(['bezeichnung' => 'OhneBedarf', 'booking_start' => '2025-06-01', 'booking_end' => '2025-06-02']);
    $leer->vbProtokoll()->create(['kunde' => 'y']);

    $html = $this->actingAs($this->user)->get(route('vb-protokoll.create', $this->target))->assertOk()->getContent();

    expect(strpos($html, 'Derby 2025'))->toBeLessThan(strpos($html, 'Zirkus'))
        ->and($html)->not->toContain('OhneBedarf');
});

test('viewers cannot use templates', function () {
    $viewer = User::factory()->create(['role' => 'viewer']);
    $this->actingAs($viewer)->post(route('vb-protokoll.fromTemplate', [$this->target, $this->source]))->assertForbidden();
});
