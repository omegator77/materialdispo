<?php

use App\Models\CameraConfig;
use App\Models\Geraetetyp;
use App\Models\Item;
use App\Models\Production;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\VbProtokoll;
use App\Services\SlackVorgangSync;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->unit = Unit::create(['bezeichnung' => 'Technik']);
    $this->typ = fn (string $name) => Geraetetyp::create(['bezeichnung' => $name, 'units_id' => $this->unit->id]);
    $this->mk = fn (Geraetetyp $t, string $nr, array $extra = []) => Item::create([
        'units_id' => $this->unit->id, 'geraetetyp_id' => $t->id, 'bezeichnung' => $t->bezeichnung, 'nummer' => $nr,
    ] + $extra);

    $this->camT = ($this->typ)('LDX');
    $this->tripT = ($this->typ)('Sachtler');
    $this->prod = Production::create(['bezeichnung' => 'Neu', 'booking_start' => '2026-12-10', 'booking_end' => '2026-12-12']);
    $this->vb = VbProtokoll::create(['production_id' => $this->prod->id]);
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->url = route('productions.vbVorschlag', $this->prod);
    $this->camLine = fn (array $extra = []) => $this->vb->anforderungen()->create(array_merge([
        'cam_number' => '1', 'geraetetyp_id' => $this->camT->id, 'anzahl' => 1,
        'tripod_geraetetyp_id' => $this->tripT->id,
    ], $extra));
});

test('Admin sieht Vorschau mit Kamerazug und Material', function () {
    ($this->mk)($this->camT, 'K-1');
    ($this->mk)($this->camT, 'K-2');
    ($this->mk)($this->tripT, 'S-1');
    $kabelT = ($this->typ)('Kabelrolle');
    ($this->mk)($kabelT, 'KR-1');
    ($this->camLine)();
    $this->vb->anforderungen()->create(['geraetetyp_id' => $kabelT->id, 'anzahl' => 1]);

    $this->actingAs($this->admin)->get($this->url)
        ->assertOk()
        ->assertSee('Packliste aus VB-Protokoll')
        ->assertSee('Cam 1')
        ->assertSee('LDX')
        ->assertSee('Sachtler')
        ->assertSee('Kabelrolle')
        ->assertSee('Übernehmen')
        ->assertSee('rigs[0][camera]', false)
        ->assertSee('rigs[0][take]', false);
});

test('ohne Offenes zeigt Alles gedeckt ohne Übernehmen-Button', function () {
    $this->actingAs($this->admin)->get($this->url)
        ->assertOk()
        ->assertSee('Alles gedeckt')
        ->assertDontSee('type="submit"', false);
});

test('Lücke ist sichtbar', function () {
    ($this->mk)($this->camT, 'K-1');
    ($this->camLine)();

    $this->actingAs($this->admin)->get($this->url)->assertOk()->assertSee('keins frei');
});

test('Zugzeile ohne Kamera-Typ zeigt Hinweis', function () {
    ($this->camLine)(['geraetetyp_id' => null]);

    $this->actingAs($this->admin)->get($this->url)->assertOk()->assertSee('ohne Typ');
});

test('Mietgerät zeigt Warnung', function () {
    $supplier = Supplier::create(['bezeichnung' => 'Verleih']);
    ($this->mk)($this->camT, 'K-1', ['suppliers_id' => $supplier->id]);
    ($this->camLine)(['tripod_geraetetyp_id' => null]);

    $this->actingAs($this->admin)->get($this->url)->assertOk()->assertSee('noch nicht gemietet');
});

test('POST übernimmt und leitet zur Packliste', function () {
    $cam = ($this->mk)($this->camT, 'K-1');
    $tri = ($this->mk)($this->tripT, 'S-1');
    $this->mock(SlackVorgangSync::class)->shouldReceive('syncProduction');

    $this->actingAs($this->admin)->post($this->url, [
        'rigs' => [0 => ['take' => '1', 'camera' => $cam->id, 'lens' => '', 'tripod' => $tri->id, 'tripod_head' => '', 'large_lens_adapter' => '']],
        'items' => [''],
    ])->assertRedirect(route('productions.show', $this->prod))->assertSessionHas('success');

    $cfg = CameraConfig::where('production_id', $this->prod->id)->first();
    expect($cfg)->not->toBeNull()
        ->and($cfg->item_id)->toBe($cam->id)
        ->and((int) $cfg->tripod)->toBe($tri->id)
        ->and($cfg->lens)->toBeNull();
});

test('POST mit Konflikt meldet Übersprungenes', function () {
    $cam = ($this->mk)($this->camT, 'K-1');
    $this->mock(SlackVorgangSync::class)->shouldReceive('syncProduction');
    $other = Production::create(['bezeichnung' => 'Parallel', 'booking_start' => '2026-12-11', 'booking_end' => '2026-12-13']);
    $other->items()->attach($cam->id);

    $this->actingAs($this->admin)->post($this->url, [
        'rigs' => [0 => ['take' => '1', 'camera' => $cam->id]],
        'items' => [$cam->id],
    ])->assertRedirect(route('productions.show', $this->prod))
        ->assertSessionHas('warning', fn ($w) => str_contains($w, 'LDX'));

    expect(CameraConfig::where('production_id', $this->prod->id)->count())->toBe(0);
});

test('Viewer: GET und POST liefern 403', function () {
    $viewer = User::factory()->create(['role' => 'viewer']);

    $this->actingAs($viewer)->get($this->url)->assertForbidden();
    $this->actingAs($viewer)->post($this->url, [])->assertForbidden();
});

test('Validierung: unbekannte Geräte-ID wird abgelehnt', function () {
    $this->actingAs($this->admin)->post($this->url, [
        'rigs' => [0 => ['take' => '1', 'camera' => 999999]],
    ])->assertSessionHasErrors('rigs.0.camera');

    $this->actingAs($this->admin)->post($this->url, ['items' => [999999]])->assertSessionHasErrors('items.0');

    expect(CameraConfig::count())->toBe(0)
        ->and(DB::table('item_production')->count())->toBe(0);
});

test('Produktionsnamen mit Sonderzeichen sind maskiert', function () {
    $this->prod->update(['bezeichnung' => 'Müller & <b>Co</b>']);

    $this->actingAs($this->admin)->get($this->url)
        ->assertOk()
        ->assertSee('Müller &amp; &lt;b&gt;Co&lt;/b&gt;', false)
        ->assertDontSee('<b>Co</b>', false);
});

test('POST mit doppelt gewähltem Gerät meldet doppelt ausgewählt', function () {
    $e = ($this->mk)(($this->typ)('Kabel'), 'KA-1');
    $this->mock(SlackVorgangSync::class)->shouldReceive('syncProduction');

    $this->actingAs($this->admin)->post($this->url, ['items' => [$e->id, $e->id]])
        ->assertRedirect(route('productions.show', $this->prod))
        ->assertSessionHas('warning', fn ($w) => str_contains($w, 'doppelt ausgewählt'));

    expect(DB::table('item_production')->where('production_id', $this->prod->id)->count())->toBe(1);
});

test('nur nicht übernehmbarer Zug zeigt keinen Übernehmen-Button', function () {
    ($this->camLine)();

    $this->actingAs($this->admin)->get($this->url)
        ->assertOk()
        ->assertSee('keine Kamera frei')
        ->assertSee('Nichts zum Übernehmen.')
        ->assertDontSee('type="submit"', false);
});

test('übernehmbarer Zug oder Bedarf zeigt den Übernehmen-Button', function () {
    ($this->mk)($this->camT, 'K-1');
    ($this->camLine)();
    $this->actingAs($this->admin)->get($this->url)->assertOk()->assertSee('type="submit"', false)->assertDontSee('Nichts zum Übernehmen.');

    $kabelT = ($this->typ)('Kabelrolle');
    ($this->mk)($kabelT, 'KR-1');
    $this->vb->anforderungen()->create(['geraetetyp_id' => $kabelT->id, 'anzahl' => 1]);
    $this->vb->anforderungen()->where('cam_number', '1')->delete();
    $this->actingAs($this->admin)->get($this->url)->assertOk()->assertSee('type="submit"', false);
});

test('Mietgerät im Material zeigt noch nicht gemietet', function () {
    $supplier = Supplier::create(['bezeichnung' => 'Verleih']);
    $kabelT = ($this->typ)('Kabelrolle');
    ($this->mk)($kabelT, 'KR-1', ['suppliers_id' => $supplier->id]);
    $this->vb->anforderungen()->create(['geraetetyp_id' => $kabelT->id, 'anzahl' => 1]);

    $this->actingAs($this->admin)->get($this->url)->assertOk()
        ->assertSee('noch nicht gemietet')
        ->assertSee('(Miete, noch nicht gemietet)');
});

test('Alternative im Kamerazug zeigt Mietwarnung in der Option', function () {
    $supplier = Supplier::create(['bezeichnung' => 'Verleih']);
    ($this->mk)($this->camT, 'K-1');
    ($this->mk)($this->camT, 'K-2', ['suppliers_id' => $supplier->id]);
    ($this->camLine)(['tripod_geraetetyp_id' => null]);

    $this->actingAs($this->admin)->get($this->url)->assertOk()->assertSee('K-2) (Miete, noch nicht gemietet)', false);
});
