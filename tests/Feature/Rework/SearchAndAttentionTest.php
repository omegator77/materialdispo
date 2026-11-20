<?php

use App\Models\Geraetetyp;
use App\Models\Item;
use App\Models\Production;
use App\Models\Unit;
use App\Models\User;
use App\Models\VbProtokoll;
use App\Services\AttentionService;
use Carbon\Carbon;

test('global search finds devices by name and number with live status', function () {
    $user = User::factory()->create(['role' => 'viewer']);
    $unit = Unit::create(['bezeichnung' => 'Kameras']);
    $free = Item::create(['units_id' => $unit->id, 'bezeichnung' => 'LDX 86N', 'nummer' => '22']);
    $busy = Item::create(['units_id' => $unit->id, 'bezeichnung' => 'LDX 86N', 'nummer' => '23']);
    $prod = Production::create(['bezeichnung' => 'Derby', 'booking_start' => Carbon::today()->addDay()->toDateString(), 'booking_end' => Carbon::today()->addDays(3)->toDateString()]);
    $prod->items()->attach($busy->id);

    $res = $this->actingAs($user)->getJson(route('search', ['q' => 'ldx']))->assertOk()->json('results');

    $byTitle = collect($res)->keyBy('title');
    expect($byTitle['LDX 86N · Nr. 22']['badge'])->toBe('frei')
        ->and($byTitle['LDX 86N · Nr. 23']['badge'])->toContain('Derby');

    // "ldx 22" muss gezielt die Nummer treffen
    $res = $this->actingAs($user)->getJson(route('search', ['q' => 'ldx 22']))->json('results');
    expect(collect($res)->pluck('title')->all())->toBe(['LDX 86N · Nr. 22']);
});

test('global search finds productions and groups, needs login', function () {
    $user = User::factory()->create(['role' => 'user']);
    Unit::create(['bezeichnung' => 'Kamerazubehör']);
    Production::create(['bezeichnung' => 'Kameraprobe', 'booking_start' => '2026-12-01', 'booking_end' => '2026-12-02']);

    $types = collect($this->actingAs($user)->getJson(route('search', ['q' => 'kamera']))->json('results'))->pluck('type')->all();
    expect($types)->toContain('Produktion', 'Gruppe');

    auth()->logout();
    $this->getJson(route('search', ['q' => 'x']))->assertUnauthorized();
});

test('empty query returns nothing', function () {
    $this->actingAs(User::factory()->create())->getJson(route('search'))->assertOk()->assertJson(['results' => []]);
});

test('dashboard surfaces conflicts with a one-click swap and open VB needs with auto-fill', function () {
    $user = User::factory()->create(['role' => 'user']);
    $unit = Unit::create(['bezeichnung' => 'Stativ']);
    $typ = Geraetetyp::create(['units_id' => $unit->id, 'bezeichnung' => 'Sachtler']);
    $mk = fn ($nr) => Item::create(['units_id' => $unit->id, 'geraetetyp_id' => $typ->id, 'bezeichnung' => 'Sachtler', 'nummer' => $nr]);
    [$a, $b, $c] = [$mk('1'), $mk('2'), $mk('3')];

    $soon = Carbon::today()->addDays(2);
    $p1 = Production::create(['bezeichnung' => 'Eins', 'booking_start' => $soon->toDateString(), 'booking_end' => $soon->copy()->addDays(2)->toDateString()]);
    $p2 = Production::create(['bezeichnung' => 'Zwei', 'booking_start' => $soon->toDateString(), 'booking_end' => $soon->copy()->addDay()->toDateString()]);
    $p1->items()->attach($a->id);
    $p2->items()->attach($a->id); // Doppelbuchung

    $vb = VbProtokoll::create(['production_id' => $p1->id, 'kunde' => 'K']);
    $vb->anforderungen()->create(['geraetetyp_id' => $typ->id, 'unit_id' => $unit->id, 'anzahl' => 2]);

    $findings = collect(app(AttentionService::class)->findings());

    $conflict = $findings->firstWhere('kind', 'konflikt');
    expect($conflict)->not->toBeNull()
        ->and($conflict['severity'])->toBe('rot')
        ->and($conflict['action']['url'])->toContain('replace-item');

    $vbOpen = $findings->firstWhere('kind', 'vb_offen');
    expect($vbOpen['action']['url'])->toBe(route('productions.vbVorschlag', $p1))
        ->and($vbOpen['action']['method'])->toBe('GET');

    $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('Handlungsbedarf')->assertSee('Konflikt');
});

test('an empty production close to its start is flagged', function () {
    Production::create(['bezeichnung' => 'Leer', 'booking_start' => Carbon::today()->addDays(3)->toDateString(), 'booking_end' => Carbon::today()->addDays(4)->toDateString()]);

    $kinds = collect(app(AttentionService::class)->findings())->pluck('kind')->all();
    expect($kinds)->toContain('leer');
});

test('a double booking is reported once, not once per production', function () {
    $unit = Unit::create(['bezeichnung' => 'Stativ']);
    $item = Item::create(['units_id' => $unit->id, 'bezeichnung' => 'Sachtler', 'nummer' => '1']);
    $soon = Carbon::today()->addDays(2);
    foreach (['Eins', 'Zwei'] as $name) {
        Production::create(['bezeichnung' => $name, 'booking_start' => $soon->toDateString(), 'booking_end' => $soon->copy()->addDay()->toDateString()])
            ->items()->attach($item->id);
    }

    $conflicts = collect(app(AttentionService::class)->findings())->where('kind', 'konflikt');
    expect($conflicts)->toHaveCount(1);
});
