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
