<?php

use App\Models\Production;
use App\Models\User;

function vorhandeneProduktion(): Production
{
    return Production::create(['bezeichnung' => 'Tagesschau', 'booking_start' => '2026-12-01', 'booking_end' => '2026-12-02']);
}

test('Admin sieht Duplizieren in Liste und Produktionsseite', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $p = vorhandeneProduktion();
    $link = route('productions.create', ['from' => $p->id]);

    $this->actingAs($admin)->get(route('productions.index'))
        ->assertSee('Duplizieren')->assertSee(e($link), false);
    $this->actingAs($admin)->get(route('productions.show', $p))
        ->assertSee('Duplizieren')->assertSee(e($link), false);
});

test('Viewer sieht kein Duplizieren', function () {
    $viewer = User::factory()->create(['role' => 'viewer']);
    $p = vorhandeneProduktion();

    $this->actingAs($viewer)->get(route('productions.index'))->assertDontSee('Duplizieren');
    $this->actingAs($viewer)->get(route('productions.show', $p))->assertDontSee('Duplizieren');
});

test('Formular mit Vorlage schlägt Kopie-Namen vor und leere Termine', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $p = vorhandeneProduktion();

    $this->actingAs($admin)->get(route('productions.create', ['from' => $p->id]))
        ->assertOk()
        ->assertSee('value="Tagesschau (Kopie)"', false)
        ->assertSee('name="from_production_id"', false);
});

test('unbekannte Vorlage lädt das Formular ohne Vorlage', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get(route('productions.create', ['from' => 99999]))
        ->assertOk()->assertDontSee('name="from_production_id"', false);
});
