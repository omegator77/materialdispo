<?php

use App\Models\User;

test('die Anleitung beschreibt das Menü Miete & Verleih und die getrennten Listen', function () {
    $html = $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('anleitung'))->assertOk()->getContent();

    expect($html)->toContain('Miete &amp; Verleih')->toContain('Mietgeräte')
        ->toContain('Eingang')->toContain('Ausgang');
    // Die alte Einordnung unter "Verwaltung" ist weg.
    expect($html)->not->toContain('Miet- und Verleihvorgänge, sowie');
    expect($html)->not->toContain('das komplette Inventar');
});

test('die Anleitung erklärt Archiv, Rückgabe offen und Erneut mieten', function () {
    $html = $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('anleitung'))->assertOk()->getContent();

    expect($html)->toContain('Archiv')->toContain('Erneut mieten')->toContain('Rückgabe offen');
});

test('die Anleitung erklärt Bestellungen per Slack-Befehl und die Stufe Bezahlt & abgelegt', function () {
    $html = $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('anleitung'))->assertOk()->getContent();

    expect($html)->toContain('Bestellungen')->toContain('Bezahlt &amp; abgelegt')->toContain('/bestellung');
});

test('die Anleitung erklärt den Knopf Sendung angekommen mit Notiz und Zurücknehmen', function () {
    $html = $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('anleitung'))->assertOk()->getContent();

    expect($html)->toContain('Sendung angekommen')->toContain('Lagerort')->toContain('Ankunft zurücknehmen');
});
