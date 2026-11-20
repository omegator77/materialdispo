<?php

use App\Models\Item;
use App\Models\Production;
use App\Models\Unit;
use App\Models\User;

function begriffeProduction(): Production
{
    return Production::create(['bezeichnung' => 'Tagesschau', 'booking_start' => '2026-12-01', 'booking_end' => '2026-12-02']);
}

dataset('begriffePages', [
    'productions.index' => [fn () => route('productions.index')],
    'productions.show' => [fn () => route('productions.show', begriffeProduction())],
    'itemprods' => [fn () => route('itemprods')],
    'packvorgang.show' => [fn () => route('packvorgang.show', begriffeProduction())],
    'dashboard' => [fn () => route('dashboard')],
]);

test('alte Begriffe tauchen auf den Seiten nicht mehr auf', function (Closure $url) {
    $this->actingAs(User::factory()->create(['role' => 'admin']));
    begriffeProduction();

    $html = $this->get($url())->assertOk()->getContent();

    expect($html)->not->toContain('Zuordnen')
        ->not->toContain('Materialzuordnung')
        ->not->toContain('Packvorgang');
})->with('begriffePages');

test('Menuepunkt zur Produktionsuebersicht heisst Packen', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']));
    $html = $this->get(route('dashboard'))->assertOk()->getContent();

    $href = preg_quote(route('itemprods'), '/');
    preg_match_all('/<a[^>]*href="'.$href.'"[^>]*>\s*([^<]*?)\s*<\/a>/s', $html, $m);

    expect($m[1])->not->toBeEmpty();
    foreach ($m[1] as $label) {
        expect($label)->toBe('Packen');
    }
});

test('Befehlspalette enthaelt Packen', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']));
    $html = $this->get(route('dashboard'))->assertOk()->getContent();

    expect($html)->toContain('\\u0022title\\u0022:\\u0022Packen\\u0022')
        ->not->toContain('\\u0022title\\u0022:\\u0022Packliste\\u0022');
});

test('Packen abschliessen und oeffnen nutzt die neuen Meldungen', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']));
    $p = begriffeProduction();

    $this->post(route('packvorgang.complete', $p))->assertSessionHas('success', 'Packen abgeschlossen.');
    $this->post(route('packvorgang.reopen', $p))->assertSessionHas('success', 'Packen wieder geöffnet.');
});

test('gesperrtes Packen meldet den neuen Fehlertext', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']));
    $p = begriffeProduction();
    $this->post(route('packvorgang.complete', $p));
    $item = Item::create(['units_id' => Unit::create(['bezeichnung' => 'Kameras'])->id, 'bezeichnung' => 'Kamera']);

    $this->postJson(route('packvorgang.toggle', [$p, $item]))
        ->assertStatus(423)
        ->assertJsonFragment(['error' => 'Packen ist abgeschlossen und gesperrt.']);
});

test('PDF-Checkliste sagt Packen statt Packvorgang', function () {
    $p = begriffeProduction();
    $html = view('pdf.packvorgang_checklist', [
        'production' => $p,
        'cameraGroups' => collect(),
        'groupedEinzelEntries' => collect(),
        'packedIds' => collect(),
    ])->render();

    expect($html)->toContain('<title>Packen Tagesschau</title>')
        ->toContain('Packen-Checkliste: Tagesschau')
        ->not->toContain('Packvorgang');
});
