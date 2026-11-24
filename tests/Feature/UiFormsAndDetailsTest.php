<?php

use App\Models\Bestellung;
use App\Models\CameraConfig;
use App\Models\Geraetetyp;
use App\Models\Item;
use App\Models\MailingList;
use App\Models\Mieter;
use App\Models\Mietvorgang;
use App\Models\Production;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\VbProtokoll;
use App\Models\Vermietvorgang;

/**
 * Formulare, Detailseiten und übrige Seiten: gleiches Seitenraster, gleicher Seitenkopf, keine Altlasten
 * der früheren Einzelgestaltung, und jedes Eingabefeld nutzt die gemeinsame Klasse .form-control.
 */
const UI_ALTLASTEN_ALLE = [
    'bg-orange-400',
    'hover:bg-orange-500',
    'bg-gray-200 hover:bg-gray-300',
    'max-w-7xl w-11/12',
    'max-w-5xl w-11/12',
    'max-w-4xl w-11/12',
    'max-w-3xl w-11/12',
    'border-gray-300 rounded-lg shadow-md',
    'bg-gray-100 border-b',
    'bg-red-600 hover:bg-red-700',
    'bg-gray-800 hover:bg-gray-900',
];

function uiFixtures(): array
{
    $unit = Unit::forceCreate(['id' => 1, 'bezeichnung' => 'Kameras', 'sort_order' => 1]);
    $supplier = Supplier::create(['bezeichnung' => 'Verleih', 'kontakt' => 'A', 'phone' => '1', 'email' => 'a@example.test']);
    $mieter = Mieter::create(['bezeichnung' => 'Kunde', 'kontakt' => 'B', 'phone' => '2', 'email' => 'b@example.test']);
    $item = Item::create(['units_id' => $unit->id, 'bezeichnung' => 'Kamera', 'nummer' => 'K-1']);
    $production = Production::create(['bezeichnung' => 'Prod', 'booking_start' => '2026-12-01', 'booking_end' => '2026-12-02']);
    $other = Production::create(['bezeichnung' => 'Vorlage', 'booking_start' => '2026-11-01', 'booking_end' => '2026-11-02']);
    $production->items()->attach($item->id);

    return [
        'unit' => $unit, 'supplier' => $supplier, 'mieter' => $mieter, 'item' => $item,
        'production' => $production, 'other' => $other,
        'typ' => Geraetetyp::create(['units_id' => $unit->id, 'bezeichnung' => 'LDX']),
        'list' => MailingList::create(['name' => 'Dispo', 'is_default' => true]),
        'miet' => Mietvorgang::create(['suppliers_id' => $supplier->id, 'rent_start' => '2026-12-01', 'rent_end' => '2026-12-05']),
        'verm' => Vermietvorgang::create(['mieter_id' => $mieter->id, 'rent_start' => '2026-12-01', 'rent_end' => '2026-12-05']),
        'config' => CameraConfig::create(['production_id' => $production->id, 'item_id' => $item->id, 'cam_number' => 'Cam 1']),
        'vb' => VbProtokoll::forceCreate(['production_id' => $production->id, 'kunde' => 'Sky']),
        'bestellung' => Bestellung::create(['bestellt_bei' => 'Thomann', 'bestellt_was' => 'Kabel', 'bezahlt_mit' => 'Karte', 'bestellt_am' => '2026-11-20 10:00:00']),
        'user' => User::factory()->create(['role' => 'user', 'name' => 'Nora']),
    ];
}

dataset('seiten', [
    // Formulare
    'Gruppe anlegen' => [fn ($f) => route('units.create')],
    'Gruppe bearbeiten' => [fn ($f) => route('units.edit', $f['unit'])],
    'Vermieter anlegen' => [fn ($f) => route('suppliers.create')],
    'Vermieter bearbeiten' => [fn ($f) => route('suppliers.edit', $f['supplier'])],
    'Mieter anlegen' => [fn ($f) => route('mieter.create')],
    'Mieter bearbeiten' => [fn ($f) => route('mieter.edit', $f['mieter'])],
    'Gerätetyp anlegen' => [fn ($f) => route('geraetetypen.create')],
    'Gerätetyp bearbeiten' => [fn ($f) => route('geraetetypen.edit', $f['typ'])],
    'Mailingliste anlegen' => [fn ($f) => route('mailing-lists.create')],
    'Mailingliste bearbeiten' => [fn ($f) => route('mailing-lists.edit', $f['list'])],
    'Benutzer anlegen' => [fn ($f) => route('users.create')],
    'Benutzer bearbeiten' => [fn ($f) => route('users.edit', $f['user'])],
    'Gerät anlegen' => [fn ($f) => route('items.create')],
    'Gerät bearbeiten' => [fn ($f) => route('items.edit', $f['item'])],
    'Geräte erfassen' => [fn ($f) => route('items.bulk.create')],
    'Produktion anlegen' => [fn ($f) => route('productions.create')],
    'Produktion bearbeiten' => [fn ($f) => route('productions.edit', $f['production'])],
    'Mietvorgang anlegen' => [fn ($f) => route('mietvorgaenge.create')],
    'Vermietvorgang anlegen' => [fn ($f) => route('vermietvorgaenge.create')],
    'Kamerazug anlegen' => [fn ($f) => route('camera-config.create', $f['production'])],
    'Kamerazug bearbeiten' => [fn ($f) => route('camera-config.edit', $f['config'])],
    'VB-Protokoll bearbeiten' => [fn ($f) => route('vb-protokoll.edit', $f['production'])],
    'Bestellung bearbeiten' => [fn ($f) => route('bestellungen.edit', $f['bestellung'])],
    'Einstellungen' => [fn ($f) => route('settings.edit')],
    'Profil' => [fn ($f) => route('profile.edit')],
    // Detailseiten
    'Gruppe Details' => [fn ($f) => route('units.show', $f['unit'])],
    'Bestellung Details' => [fn ($f) => route('bestellungen.show', $f['bestellung'])],
    'Vermieter Details' => [fn ($f) => route('suppliers.show', $f['supplier'])],
    'Mieter Details' => [fn ($f) => route('mieter.show', $f['mieter'])],
    'Gerät Details' => [fn ($f) => route('items.show', $f['item'])],
    'Produktion Details' => [fn ($f) => route('productions.show', $f['production'])],
    'Mietvorgang Details' => [fn ($f) => route('mietvorgaenge.show', $f['miet'])],
    'Vermietvorgang Details' => [fn ($f) => route('vermietvorgaenge.show', $f['verm'])],
    'VB-Protokoll Details' => [fn ($f) => route('vb-protokoll.show', $f['production'])],
    'VB-Vorschlag' => [fn ($f) => route('productions.vbVorschlag', $f['production'])],
    'Packvorgang' => [fn ($f) => route('packvorgang.show', $f['production'])],
    'Produktion aus Vorlage' => [fn ($f) => route('productions.importFrom', [$f['production'], $f['other']])],
    // Übrige
    'Timeline' => [fn ($f) => route('timeline.items')],
    'Anleitung' => [fn ($f) => route('anleitung')],
]);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->f = uiFixtures();
});

test('Seite nutzt Seitenraster und einheitlichen Seitenkopf', function (Closure $url) {
    $html = $this->actingAs($this->admin)->get($url($this->f))->assertOk()->getContent();

    expect($html)->toContain('data-page-header')->and($html)->toContain('class="page');
})->with('seiten');

test('Seite enthält keine Altlasten der früheren Einzelgestaltung', function (Closure $url) {
    $html = $this->actingAs($this->admin)->get($url($this->f))->assertOk()->getContent();

    foreach (UI_ALTLASTEN_ALLE as $alt) {
        $this->assertFalse(str_contains($html, $alt), "Altlast '{$alt}'");
    }
})->with('seiten');

test('jedes Eingabefeld nutzt die gemeinsame Klasse form-control', function (Closure $url) {
    $html = $this->actingAs($this->admin)->get($url($this->f))->assertOk()->getContent();

    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8"?>'.$html);
    $xp = new DOMXPath($dom);
    // Das Suchfeld der Befehlspalette (Strg+K) im Seitenrahmen hat bewusst ein eigenes Aussehen.
    $nichtPalette = '[not(ancestor::*[@aria-label="Suche und Befehle"])]';
    $felder = $xp->query('//select'.$nichtPalette.' | //textarea'.$nichtPalette.' | //input[not(@type) or @type="text" or @type="email" or @type="password" or @type="number" or @type="date" or @type="tel" or @type="search" or @type="url" or @type="time" or @type="datetime-local"]'.$nichtPalette);

    $ohne = [];
    foreach ($felder as $feld) {
        if (! str_contains(' '.$feld->getAttribute('class').' ', ' form-control ')) {
            $ohne[] = $feld->nodeName.'['.($feld->getAttribute('name') ?: $feld->getAttribute('id') ?: '?').']';
        }
    }

    expect($ohne)->toBe([]);
})->with('seiten');

test('Seitenkopf ist sauber verschachtelt und der Inhalt liegt im Seitenrahmen', function (Closure $url) {
    $html = $this->actingAs($this->admin)->get($url($this->f))->assertOk()->getContent();

    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8"?>'.$html);
    $xp = new DOMXPath($dom);
    $hat = fn (string $klasse) => 'contains(concat(" ", normalize-space(@class), " "), " '.$klasse.' ")';

    // Ein überzähliges </div> im Seitenkopf schiebt den Inhalt aus dem Seitenrahmen (grauer Hintergrund bleibt leer).
    expect($xp->query('//header//*[@data-page-header]')->length)->toBe(1)
        ->and($xp->query('//header//*['.$hat('page').' or '.$hat('page-narrow').']')->length)->toBe(0)
        ->and($xp->query('//*['.$hat('min-h-screen').']//*['.$hat('page').' or '.$hat('page-narrow').']')->length)->toBeGreaterThan(0);
})->with('seiten');
