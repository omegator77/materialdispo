<?php

use App\Models\Production;
use App\Models\User;
use App\Models\VbProtokoll;

function pagesProduction(bool $withVb = false): Production
{
    $p = Production::create(['bezeichnung' => 'Tagesschau', 'booking_start' => '2026-12-01', 'booking_end' => '2026-12-02']);
    if ($withVb) {
        VbProtokoll::create(['production_id' => $p->id, 'created_by' => User::factory()->create()->id]);
    }

    return $p->fresh();
}

function activeStepLabel(string $html): string
{
    preg_match('/<(?:a|span)[^>]*aria-current="step"[^>]*>\s*([^<\s][^<]*)/s', $html, $m);

    return trim($m[1] ?? '');
}

dataset('stepPages', [
    'productions.show' => ['productions.show', false, 'Packliste'],
    'packvorgang.show' => ['packvorgang.show', false, 'Packen'],
    'vb-protokoll.show' => ['vb-protokoll.show', true, 'VB-Protokoll'],
    'vb-protokoll.create' => ['vb-protokoll.create', false, 'VB-Protokoll'],
    'vb-protokoll.edit' => ['vb-protokoll.edit', true, 'VB-Protokoll'],
    'productions.edit' => ['productions.edit', false, 'Details'],
]);

test('jede Produktionsseite zeigt die Leiste mit genau einem aktiven Schritt', function (string $route, bool $withVb, string $label) {
    $this->actingAs(User::factory()->create(['role' => 'admin']));
    $p = pagesProduction($withVb);

    $response = $this->get(route($route, $p));

    $response->assertOk()->assertSee('data-production-steps', false);
    $html = $response->getContent();
    expect(substr_count($html, 'aria-current="step"'))->toBe(1);
    expect(activeStepLabel($html))->toBe($label);
})->with('stepPages');

test('Packliste-Seite heisst Packliste und nicht Materialzuordnung', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']));
    $html = $this->get(route('productions.show', pagesProduction()))->assertOk()->getContent();

    expect($html)->toContain('Packliste')->not->toContain('Materialzuordnung')->not->toContain('Zuordnen');
});

test('Packen-Seite heisst Packen: Name', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']));
    $html = $this->get(route('packvorgang.show', pagesProduction()))->assertOk()->getContent();

    expect($html)->toContain('Packen: Tagesschau')->not->toContain('Packvorgang:');
});

test('Viewer sieht Leiste auf Packen- und VB-Seite ohne Anlegen-Link', function () {
    $this->actingAs(User::factory()->create(['role' => 'viewer']));
    $p = pagesProduction(true);
    $create = route('vb-protokoll.create', $p);

    foreach (['packvorgang.show', 'vb-protokoll.show'] as $route) {
        $this->get(route($route, $p))->assertOk()->assertSee('data-production-steps', false)->assertDontSee($create, false);
    }

    $q = Production::create(['bezeichnung' => 'Ohne VB', 'booking_start' => '2026-12-01', 'booking_end' => '2026-12-02']);
    $this->get(route('packvorgang.show', $q))->assertOk()->assertSee('fehlt')->assertDontSee(route('vb-protokoll.create', $q), false);
});

test('Packen-Seite: Badge der Leiste ist live an Alpine gebunden', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']));
    $p = Production::create(['bezeichnung' => 'Live', 'booking_start' => '2026-12-01', 'booking_end' => '2026-12-02']);

    $html = $this->get(route('packvorgang.show', $p))->assertOk()->getContent();

    expect($html)->toMatch('/<span[^>]*x-text="packedCount \+ \'\/\' \+ total"[^>]*>0\/0<\/span>/');
});
