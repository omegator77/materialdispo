<?php

use App\Models\Production;
use App\Models\User;
use Illuminate\Support\Str;

test('Formular listet frühere Produktionen als Links', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $ps = collect(['Tagesschau', 'Sportstudio', 'Landtag'])->map(fn ($n) => Production::create([
        'bezeichnung' => $n, 'booking_start' => '2026-12-01', 'booking_end' => '2026-12-01',
    ]));

    $res = $this->actingAs($admin)->get(route('productions.create'))->assertOk();

    $res->assertSee('data-template-list', false);
    $liste = Str::between($res->getContent(), 'data-template-list', 'data-template-list-end');
    foreach ($ps as $p) {
        expect($liste)->toContain(e(route('productions.create', ['from' => $p->id])))->toContain($p->bezeichnung);
    }
    $res->assertDontSee('Vorlage (optional)');
});

test('mit gewählter Vorlage steht der Hinweis statt der Liste da', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $p = Production::create(['bezeichnung' => 'Tagesschau', 'booking_start' => '2026-12-01', 'booking_end' => '2026-12-01']);

    $this->actingAs($admin)->get(route('productions.create', ['from' => $p->id]))
        ->assertSee('Vorlage:')->assertDontSee('data-template-list', false);
});

test('Suche lässt bereits angezeigte Produktionen aus', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $a = Production::create(['bezeichnung' => 'Tagesschau A', 'booking_start' => '2026-12-01', 'booking_end' => '2026-12-01']);
    $b = Production::create(['bezeichnung' => 'Tagesschau B', 'booking_start' => '2026-11-01', 'booking_end' => '2026-11-01']);

    $ids = $this->actingAs($admin)->getJson(route('productions.searchTemplates', ['q' => 'Tages', 'exclude' => (string) $a->id]))
        ->assertOk()->json('*.id');

    expect($ids)->toBe([$b->id]);
});
