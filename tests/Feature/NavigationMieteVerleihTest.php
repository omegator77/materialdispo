<?php

use App\Models\User;

function navHtml(string $role = 'admin', string $route = 'dashboard', array $params = []): array
{
    $user = User::factory()->create(['role' => $role]);
    $html = test()->actingAs($user)->get(route($route, $params))->assertOk()->getContent();
    $split = strpos($html, 'Responsive Navigation Menu');
    expect($split)->not->toBeFalse();

    return [substr($html, 0, $split), substr($html, $split)];
}

function mieteLinks(): array
{
    return [
        'Mietgeräte' => route('items.rented'),
        'Vermieter' => route('suppliers.index'),
        'Mietvorgänge' => route('mietvorgaenge.index'),
        'Mieter' => route('mieter.index'),
        'Vermietvorgänge' => route('vermietvorgaenge.index'),
    ];
}

test('das Menü "Miete & Verleih" steht vor "Verwaltung"', function () {
    [$desktop] = navHtml();

    expect($desktop)->toContain('Miete &amp; Verleih')->toContain('Verwaltung');
    expect(strpos($desktop, 'Miete &amp; Verleih'))->toBeLessThan(strpos($desktop, 'Verwaltung'));
});

test('die fünf Ziele liegen im Menü Miete & Verleih und nicht mehr in Verwaltung', function () {
    [$desktop] = navHtml();
    $miete = strpos($desktop, 'Miete &amp; Verleih');
    $verwaltung = strpos($desktop, 'Verwaltung');

    foreach (mieteLinks() as $label => $url) {
        $pos = strpos($desktop, 'href="'.$url.'"');
        expect($pos)->not->toBeFalse("Link {$label} fehlt");
        expect($pos)->toBeGreaterThan($miete)->and($pos)->toBeLessThan($verwaltung);
    }

    $rest = substr($desktop, $verwaltung);
    foreach (['units.index', 'geraetetypen.index', 'mailing-lists.index'] as $route) {
        expect($rest)->toContain('href="'.route($route).'"');
    }
    foreach (['suppliers.index', 'mietvorgaenge.index', 'mieter.index', 'vermietvorgaenge.index'] as $route) {
        expect($rest)->not->toContain('href="'.route($route).'"');
    }
});

test('die Gruppen heißen Eingang (wir mieten) und Ausgang (wir verleihen)', function () {
    [$desktop, $mobile] = navHtml();

    foreach ([$desktop, $mobile] as $part) {
        expect($part)->toContain('Eingang (wir mieten)')->toContain('Ausgang (wir verleihen)');
        expect(strpos($part, 'Eingang (wir mieten)'))->toBeLessThan(strpos($part, 'Ausgang (wir verleihen)'));
    }
});

test('das Mobilmenü hat dieselbe Struktur', function () {
    [, $mobile] = navHtml();
    $verwaltung = strpos($mobile, 'Stammdaten');
    expect($verwaltung)->not->toBeFalse();

    foreach (mieteLinks() as $label => $url) {
        $pos = strpos($mobile, 'href="'.$url.'"');
        expect($pos)->not->toBeFalse("Mobil-Link {$label} fehlt");
        expect($pos)->toBeLessThan($verwaltung);
    }
});

test('der aktive Zustand stimmt', function () {
    $active = fn (string $desktop) => preg_match('/border-indigo-400[^>]*>\s*Miete &amp; Verleih/s', $desktop) === 1;
    $geraeteActive = fn (string $desktop) => preg_match('/border-indigo-400[^>]*>\s*Geräte\s*</s', $desktop) === 1;

    foreach (['items.rented', 'suppliers.index', 'mietvorgaenge.index', 'mieter.index', 'vermietvorgaenge.index'] as $route) {
        [$desktop] = navHtml('admin', $route);
        expect($active($desktop))->toBeTrue("{$route}: Miete & Verleih nicht aktiv");
        expect($geraeteActive($desktop))->toBeFalse("{$route}: Geräte fälschlich aktiv");
    }

    [$desktop] = navHtml('admin', 'items.index');
    expect($geraeteActive($desktop))->toBeTrue()->and($active($desktop))->toBeFalse();

    [$desktop] = navHtml('admin', 'units.index');
    expect($active($desktop))->toBeFalse();
});

test('Viewer sieht das Menü und die Links', function () {
    [$desktop] = navHtml('viewer');

    expect($desktop)->toContain('Miete &amp; Verleih');
    foreach (mieteLinks() as $url) {
        expect($desktop)->toContain('href="'.$url.'"');
    }
});

test('die Befehlspalette findet Mietgeräte', function () {
    $html = test()->actingAs(User::factory()->create(['role' => 'admin']))->get(route('dashboard'))->getContent();
    $palette = substr($html, (int) strpos($html, 'commandPalette'));

    // Die Aktionen stehen JSON-kodiert (mehrfach maskiert) im Attribut: auf Pfad und Stichwort prüfen.
    expect($palette)->toContain('mietgeraete')->toContain('mietpool');
});
