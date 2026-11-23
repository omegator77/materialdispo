<?php

use App\Models\Item;
use App\Models\Mietvorgang;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;

function archSetup(): array
{
    // Kameras haben die feste Gruppen-ID 1 (Tabellenansicht je Gruppe); MySQL setzt AUTO_INCREMENT nicht zurück.
    $cams = Unit::forceCreate(['id' => 1, 'bezeichnung' => 'Kameras']);
    $audio = Unit::create(['bezeichnung' => 'Audio']);

    return [
        'admin' => User::factory()->create(['role' => 'admin']),
        'cams' => $cams,
        'audio' => $audio,
        'supplier' => Supplier::create(['bezeichnung' => 'Pixmade']),
    ];
}

function archItem(array $s, string $name, ?Unit $unit = null): Item
{
    return Item::create(['units_id' => ($unit ?? $s['cams'])->id, 'bezeichnung' => $name, 'suppliers_id' => $s['supplier']->id]);
}

function archVorgang(array $s, Item $item, string $start, string $end, bool $complete = false): Mietvorgang
{
    $mv = Mietvorgang::create([
        'suppliers_id' => $s['supplier']->id, 'rent_start' => $start, 'rent_end' => $end,
        'transport_end_confirmed_at' => $complete ? now() : null,
        'bereit_zur_rueckgabe_confirmed_at' => $complete ? now() : null,
    ]);
    $mv->items()->attach($item->id);

    return $mv;
}

function rentedHtml(array $s, array $params = [], ?User $user = null): string
{
    return test()->actingAs($user ?? $s['admin'])->get(route('items.rented', $params))->assertOk()->getContent();
}

function archivBlock(string $html): string
{
    $pos = strpos($html, 'data-rent-archive');

    return $pos === false ? '' : substr($html, $pos);
}

beforeEach(fn () => $this->travelTo('2026-12-10'));

test('abgeschlossenes Gerät steht im Archiv, aktuelles davor', function () {
    $s = archSetup();
    archVorgang($s, archItem($s, 'Alt-Kamera'), '2026-01-01', '2026-01-05', true);
    archVorgang($s, archItem($s, 'Aktuell-Kamera'), '2026-12-08', '2026-12-12');

    $html = rentedHtml($s);

    expect($html)->toContain('Archiv (1)');
    expect(strpos($html, 'Aktuell-Kamera'))->toBeLessThan(strpos($html, 'data-rent-archive'));
    expect(archivBlock($html))->toContain('Alt-Kamera')->not->toContain('Aktuell-Kamera');
    expect(substr($html, 0, strpos($html, 'data-rent-archive')))->not->toContain('Alt-Kamera');
});

test('Gerät ohne Mietvorgang bleibt aktuell mit "noch nicht gemietet"', function () {
    $s = archSetup();
    archItem($s, 'Neu-Kamera');

    $html = rentedHtml($s);

    expect($html)->toContain('Neu-Kamera')->toContain('noch nicht gemietet')->not->toContain('Archiv (');
});

test('abgelaufen und nicht zurückgegeben bleibt aktuell mit "Rückgabe offen"', function () {
    $s = archSetup();
    archVorgang($s, archItem($s, 'Offen-Kamera'), '2026-11-01', '2026-11-05');

    $html = rentedHtml($s);

    expect($html)->toContain('Offen-Kamera')->toContain('Rückgabe offen')->not->toContain('Archiv (');
});

test('Mischfall zeigt den kommenden Zeitraum in der aktuellen Liste', function () {
    $s = archSetup();
    $item = archItem($s, 'Misch-Kamera');
    archVorgang($s, $item, '2026-01-01', '2026-01-05', true);
    archVorgang($s, $item, '2026-12-20', '2026-12-24');

    $html = rentedHtml($s);

    expect($html)->toContain('20.12.2026')->not->toContain('Archiv (');
});

test('ohne archivierte Geräte gibt es keine Archiv-Sektion', function () {
    $s = archSetup();
    archItem($s, 'Neu-Kamera');

    expect(rentedHtml($s))->not->toContain('data-rent-archive');
});

test('Archiv zeigt letzten Zeitraum und Vermieter', function () {
    $s = archSetup();
    archVorgang($s, archItem($s, 'Alt-Kamera'), '2026-03-01', '2026-03-05', true);

    $archiv = archivBlock(rentedHtml($s));

    expect($archiv)->toContain('zuletzt')->toContain('01.03.2026')->toContain('05.03.2026')->toContain('Pixmade');
});

test('der Gruppenfilter gilt auch fürs Archiv', function () {
    $s = archSetup();
    archVorgang($s, archItem($s, 'Alt-Kamera'), '2026-01-01', '2026-01-05', true);
    archVorgang($s, archItem($s, 'Alt-Mikro', $s['audio']), '2026-01-01', '2026-01-05', true);

    expect(archivBlock(rentedHtml($s, ['unit_id' => $s['audio']->id])))->toContain('Alt-Mikro')->not->toContain('Alt-Kamera');
    // Kameratabelle (feste Gruppen-ID 1) mit Archiv
    expect(archivBlock(rentedHtml($s, ['unit_id' => 1])))->toContain('Alt-Kamera')->not->toContain('Alt-Mikro');
});

test('Archivzeilen tragen data-search und bulk-select nur für Benutzer', function () {
    $s = archSetup();
    $item = archVorgang($s, archItem($s, 'Alt-Kamera'), '2026-01-01', '2026-01-05', true)->items()->first();

    $admin = archivBlock(rentedHtml($s));
    expect($admin)->toContain('data-search=')->toContain('data-item-id="'.$item->id.'"')->toContain('bulk-select');

    $viewer = archivBlock(rentedHtml($s, [], User::factory()->create(['role' => 'viewer'])));
    expect($viewer)->toContain('data-search=')->not->toContain('bulk-select');
});

test('Geräte (items.index) hat kein Archiv und zeigt nur eigene Geräte', function () {
    $s = archSetup();
    Item::create(['units_id' => $s['cams']->id, 'bezeichnung' => 'Eigene-Kamera']);
    archVorgang($s, archItem($s, 'Alt-Kamera'), '2026-01-01', '2026-01-05', true);

    $html = $this->actingAs($s['admin'])->get(route('items.index'))->assertOk()->getContent();

    expect($html)->toContain('Eigene-Kamera')->not->toContain('Alt-Kamera')->not->toContain('data-rent-archive');
});

test('Viewer sieht das Archiv, aber keinen Button "Erneut mieten"', function () {
    $s = archSetup();
    archVorgang($s, archItem($s, 'Alt-Kamera'), '2026-01-01', '2026-01-05', true);
    $viewer = User::factory()->create(['role' => 'viewer']);

    $html = rentedHtml($s, [], $viewer);

    expect($html)->toContain('Archiv (1)')->toContain('Alt-Kamera')->not->toContain('Erneut mieten');
});

test('Archiv-Checkboxen tragen die Projekt-Klasse form-check', function () {
    $s = archSetup();
    archVorgang($s, archItem($s, 'Alt-Kamera'), '2026-01-01', '2026-01-05', true);

    preg_match_all('/<input[^>]*type="checkbox"[^>]*>/', archivBlock(rentedHtml($s)), $m);

    expect($m[0])->not->toBeEmpty();
    foreach ($m[0] as $tag) {
        expect($tag)->toContain('form-check');
    }
});

test('sind alle Mietgeräte archiviert, sagt die aktuelle Liste nicht "Keine Geräte gefunden"', function () {
    $s = archSetup();
    archVorgang($s, archItem($s, 'Alt-Kamera'), '2026-01-01', '2026-01-05', true);

    foreach ([[], ['unit_id' => 1]] as $params) {
        $html = rentedHtml($s, $params);
        $aktuell = substr($html, 0, strpos($html, 'data-rent-archive'));

        expect($html)->toContain('Archiv (1)');
        // Die Tabellenzeile sagt "Keine aktuellen Mietgeräte." (der versteckte Such-Hinweis heißt weiter "gefunden").
        expect($aktuell)->toMatch('/class="empty-state">\s*Keine aktuellen Mietgeräte\./')
            ->not->toMatch('/class="empty-state">\s*Keine [^<]*gefunden/');
    }
});

test('ohne Archiv bleibt die gewohnte Leer-Meldung', function () {
    $s = archSetup();

    expect(rentedHtml($s))->toContain('Keine Geräte gefunden');
});
