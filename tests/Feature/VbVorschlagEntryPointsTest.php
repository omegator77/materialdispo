<?php

use App\Models\Geraetetyp;
use App\Models\Production;
use App\Models\Unit;
use App\Models\User;
use App\Models\VbProtokoll;

beforeEach(function () {
    $unit = Unit::create(['bezeichnung' => 'Technik']);
    $this->typ = Geraetetyp::create(['bezeichnung' => 'LDX', 'units_id' => $unit->id]);
    $this->prod = Production::create(['bezeichnung' => 'Neu', 'booking_start' => '2026-12-10', 'booking_end' => '2026-12-12']);
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->viewer = User::factory()->create(['role' => 'viewer']);
    $this->text = 'Packliste aus VB-Protokoll vorschlagen';
    $this->url = route('productions.vbVorschlag', $this->prod);
    $this->withVb = function () {
        $vb = VbProtokoll::create(['production_id' => $this->prod->id]);
        $vb->anforderungen()->create(['geraetetyp_id' => $this->typ->id, 'anzahl' => 1]);
    };
});

test('Admin sieht Button in Schnelleingabe, Leer-Zustand und VB-Protokoll', function () {
    ($this->withVb)();

    $html = $this->actingAs($this->admin)->get(route('productions.show', $this->prod))
        ->assertOk()->assertSee($this->text)->assertSee($this->url, false)
        ->assertDontSee('Offenen Bedarf automatisch zuordnen')->getContent();
    expect(substr_count($html, $this->text))->toBe(2);
    expect($html)->toContain('data-empty-state');

    $this->actingAs($this->admin)->get(route('vb-protokoll.show', $this->prod->id))
        ->assertOk()->assertSee($this->text)->assertSee($this->url, false);
});

test('Ohne VB-Protokoll kein Button', function () {
    $this->actingAs($this->admin)->get(route('productions.show', $this->prod))
        ->assertOk()->assertDontSee($this->text);
});

test('VB-Protokoll ohne Anforderung zeigt keinen Button', function () {
    VbProtokoll::create(['production_id' => $this->prod->id]);
    $this->actingAs($this->admin)->get(route('productions.show', $this->prod))
        ->assertOk()->assertDontSee($this->text);
});

test('Viewer sieht den Button nirgends', function () {
    ($this->withVb)();
    $this->actingAs($this->viewer)->get(route('productions.show', $this->prod))
        ->assertOk()->assertDontSee($this->text);
    $this->actingAs($this->viewer)->get(route('vb-protokoll.show', $this->prod->id))
        ->assertOk()->assertDontSee($this->text);
});
