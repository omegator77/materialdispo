<?php

use App\Models\CameraConfig;
use App\Models\Geraetetyp;
use App\Models\Item;
use App\Models\Production;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\VbProtokoll;
use App\Services\VbVorschlagService;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->unit = Unit::create(['bezeichnung' => 'Technik']);
    $this->typ = fn (string $name) => Geraetetyp::create(['bezeichnung' => $name, 'units_id' => $this->unit->id]);
    $this->mk = fn (Geraetetyp $t, string $nr, array $extra = []) => Item::create([
        'units_id' => $this->unit->id, 'geraetetyp_id' => $t->id, 'bezeichnung' => $t->bezeichnung, 'nummer' => $nr,
    ] + $extra);

    $this->camT = ($this->typ)('LDX');
    $this->lensT = ($this->typ)('Objektiv 22x');
    $this->tripT = ($this->typ)('Sachtler');
    $this->headT = ($this->typ)('Vinten Kopf');

    $this->prod = Production::create(['bezeichnung' => 'Neu', 'booking_start' => '2026-12-10', 'booking_end' => '2026-12-12']);
    $this->vb = VbProtokoll::create(['production_id' => $this->prod->id]);

    $this->propose = fn () => app(VbVorschlagService::class)->propose($this->prod->fresh());
    $this->camLine = fn (array $extra = []) => $this->vb->anforderungen()->create(array_merge([
        'cam_number' => '1', 'geraetetyp_id' => $this->camT->id, 'anzahl' => 1,
        'lens_geraetetyp_id' => $this->lensT->id, 'tripod_geraetetyp_id' => $this->tripT->id,
    ], $extra));
});

test('ohne VB-Protokoll ist der Vorschlag leer', function () {
    $this->vb->delete();

    expect(($this->propose)()['empty'])->toBeTrue();
});

test('Kamerazeile ergibt einen Zug mit freiem Gerät je verlangtem Typ', function () {
    foreach ([$this->camT, $this->lensT, $this->tripT] as $t) {
        ($this->mk)($t, '1');
        ($this->mk)($t, '2');
    }
    ($this->camLine)();

    $out = ($this->propose)();

    expect($out['empty'])->toBeFalse()
        ->and($out['rigs'])->toHaveCount(1);
    $rig = $out['rigs'][0];
    expect($rig['takeable'])->toBeTrue()
        ->and($rig['cam_label'])->toBe('Cam 1')
        ->and($rig['slots']['camera']['item']->geraetetyp_id)->toBe($this->camT->id)
        ->and($rig['slots']['lens']['item']->geraetetyp_id)->toBe($this->lensT->id)
        ->and($rig['slots']['tripod']['item']->geraetetyp_id)->toBe($this->tripT->id)
        ->and($rig['slots']['camera']['alternatives'])->toHaveCount(1);
});

test('Slot ohne Typ im VB bleibt leer ohne Lücke', function () {
    foreach ([$this->camT, $this->lensT, $this->tripT] as $t) {
        ($this->mk)($t, '1');
    }
    ($this->camLine)();

    $slot = ($this->propose)()['rigs'][0]['slots']['tripod_head'];

    expect($slot['item'])->toBeNull()->and($slot['gap'])->toBeFalse();
});

test('Typ verlangt aber nichts frei ist eine Lücke, der Zug bleibt übernehmbar', function () {
    ($this->mk)($this->camT, '1');
    ($this->mk)($this->lensT, '1');
    ($this->camLine)();

    $rig = ($this->propose)()['rigs'][0];

    expect($rig['slots']['tripod']['gap'])->toBeTrue()
        ->and($rig['slots']['tripod']['item'])->toBeNull()
        ->and($rig['takeable'])->toBeTrue();
});

test('Keine freie Kamera ergibt keinen übernehmbaren Zug', function () {
    ($this->camLine)();

    $rig = ($this->propose)()['rigs'][0];

    expect($rig['camera_missing'])->toBeTrue()->and($rig['takeable'])->toBeFalse();
});

test('Kamerazeile ohne Kamera-Typ', function () {
    ($this->camLine)(['geraetetyp_id' => null]);

    $rig = ($this->propose)()['rigs'][0];

    expect($rig['no_type'])->toBeTrue()->and($rig['takeable'])->toBeFalse();
});

test('bestehender Zug deckt die erste Zeile desselben Kamera-Typs', function () {
    $existing = ($this->mk)($this->camT, '1');
    ($this->mk)($this->camT, '2');
    CameraConfig::create(['production_id' => $this->prod->id, 'cam_number' => 'Cam 1', 'item_id' => $existing->id]);
    ($this->camLine)(['cam_number' => '1']);
    ($this->camLine)(['cam_number' => '2']);

    $out = ($this->propose)();

    expect($out['rigs'])->toHaveCount(1)
        ->and($out['rigs'][0]['cam_label'])->toBe('Cam 2');
});

test('Gerät wird nie doppelt vorgeschlagen', function () {
    ($this->mk)($this->camT, '1');
    ($this->mk)($this->camT, '2');
    $lens = ($this->mk)($this->lensT, '1');
    ($this->camLine)(['cam_number' => '1']);
    ($this->camLine)(['cam_number' => '2']);
    // Material verlangt denselben Objektiv-Typ nochmal
    $this->vb->anforderungen()->create(['geraetetyp_id' => $this->lensT->id, 'anzahl' => 1]);

    $out = ($this->propose)();

    $cams = collect($out['rigs'])->map(fn ($r) => $r['slots']['camera']['item']->id);
    expect($cams->unique())->toHaveCount(2);
    expect($out['rigs'][0]['slots']['lens']['item']->id)->toBe($lens->id)
        ->and($out['rigs'][1]['slots']['lens']['item'])->toBeNull();
    expect($out['needs'][0]['items'])->toBe([])->and($out['needs'][0]['missing'])->toBe(1);
});

test('übriger Bedarf wird aus Typ und Anzahl gefüllt', function () {
    ($this->mk)($this->lensT, '1');
    ($this->mk)($this->lensT, '2');
    $this->vb->anforderungen()->create(['geraetetyp_id' => $this->lensT->id, 'anzahl' => 2]);

    $need = ($this->propose)()['needs'][0];

    expect($need['items'])->toHaveCount(2)->and($need['missing'])->toBe(0)->and($need['requested'])->toBe(2);
});

test('übriger Bedarf meldet Fehlmenge bei zu wenigen freien Geräten', function () {
    ($this->mk)($this->lensT, '1');
    $this->vb->anforderungen()->create(['geraetetyp_id' => $this->lensT->id, 'anzahl' => 2]);

    $need = ($this->propose)()['needs'][0];

    expect($need['items'])->toHaveCount(1)->and($need['missing'])->toBe(1);
});

test('bereits gedeckter Bedarf fehlt im Vorschlag', function () {
    $item = ($this->mk)($this->lensT, '1');
    $this->prod->items()->attach($item->id);
    $this->vb->anforderungen()->create(['geraetetyp_id' => $this->lensT->id, 'anzahl' => 1]);

    $out = ($this->propose)();

    expect($out['needs'])->toBe([])->and($out['empty'])->toBeTrue();
});

test('Eigentum kommt vor Miete, Mietgerät ohne Mietvorgang trägt Warnung', function () {
    $supplier = Supplier::create(['bezeichnung' => 'Verleih']);
    ($this->mk)($this->camT, '1', ['suppliers_id' => $supplier->id]);
    $own = ($this->mk)($this->camT, '2');
    $this->vb->anforderungen()->create(['cam_number' => '1', 'geraetetyp_id' => $this->camT->id, 'anzahl' => 1]);

    $slot = ($this->propose)()['rigs'][0]['slots']['camera'];
    expect($slot['item']->id)->toBe($own->id)->and($slot['warning'])->toBeNull();

    $own->delete();

    $slot = ($this->propose)()['rigs'][0]['slots']['camera'];
    expect($slot['item']->suppliers_id)->toBe($supplier->id)->and($slot['warning'])->toBe('noch nicht gemietet');
});

test('propose() ändert nichts', function () {
    ($this->mk)($this->camT, '1');
    ($this->mk)($this->lensT, '1');
    ($this->camLine)();
    $this->vb->anforderungen()->create(['geraetetyp_id' => $this->lensT->id, 'anzahl' => 1]);
    $count = fn () => [DB::table('camera_configs')->count(), DB::table('item_production')->count()];
    $before = $count();

    ($this->propose)();

    expect($count())->toBe($before);
});

test('ein Zug ohne Kamera-Typ verbraucht kein Objektiv, das der nächste Zug braucht', function () {
    ($this->mk)($this->camT, '1');
    $lens = ($this->mk)($this->lensT, '1');
    ($this->camLine)(['geraetetyp_id' => null, 'tripod_geraetetyp_id' => null]);
    ($this->camLine)(['cam_number' => '2', 'tripod_geraetetyp_id' => null]);

    $out = ($this->propose)();

    foreach ($out['rigs'][0]['slots'] as $slot) {
        expect($slot['gap'])->toBeFalse()->and($slot['item'])->toBeNull();
    }
    expect($out['rigs'][1]['slots']['lens']['item']->id)->toBe($lens->id)
        ->and($out['rigs'][1]['slots']['lens']['gap'])->toBeFalse();
});

test('ein Zug ohne freie Kamera reserviert kein Zubehör', function () {
    $otherCamT = ($this->typ)('Andere Kamera');
    ($this->mk)($this->camT, '1');
    $lens = ($this->mk)($this->lensT, '1');
    ($this->camLine)(['geraetetyp_id' => $otherCamT->id, 'tripod_geraetetyp_id' => null]);
    ($this->camLine)(['cam_number' => '2', 'tripod_geraetetyp_id' => null]);

    $out = ($this->propose)();

    expect($out['rigs'][0]['camera_missing'])->toBeTrue()
        ->and($out['rigs'][0]['slots']['lens']['item'])->toBeNull()
        ->and($out['rigs'][1]['slots']['lens']['item']->id)->toBe($lens->id)
        ->and($out['rigs'][1]['slots']['lens']['gap'])->toBeFalse();
});

test('Zeile ohne Typ wird von einem übrigen vorhandenen Zug gedeckt', function () {
    $cam = ($this->mk)($this->camT, '1');
    CameraConfig::create(['production_id' => $this->prod->id, 'cam_number' => 'Cam 1', 'item_id' => $cam->id]);
    ($this->camLine)(['geraetetyp_id' => null]);

    $out = ($this->propose)();

    expect($out['rigs'])->toBe([])->and($out['empty'])->toBeTrue();
});

test('zwei Zeilen ohne Typ und ein übriger Zug lassen genau einen Eintrag', function () {
    $cam = ($this->mk)($this->camT, '1');
    CameraConfig::create(['production_id' => $this->prod->id, 'cam_number' => 'Cam 1', 'item_id' => $cam->id]);
    ($this->camLine)(['geraetetyp_id' => null, 'cam_number' => '1']);
    ($this->camLine)(['geraetetyp_id' => null, 'cam_number' => '2']);

    $out = ($this->propose)();

    expect($out['rigs'])->toHaveCount(1)->and($out['rigs'][0]['no_type'])->toBeTrue();
});

test('ein für eine getypte Zeile verbrauchter Zug deckt keine Zeile ohne Typ', function () {
    $cam = ($this->mk)($this->camT, '1');
    CameraConfig::create(['production_id' => $this->prod->id, 'cam_number' => 'Cam 1', 'item_id' => $cam->id]);
    ($this->camLine)(['cam_number' => '1']);
    ($this->camLine)(['geraetetyp_id' => null, 'cam_number' => '2']);

    $out = ($this->propose)();

    expect($out['rigs'])->toHaveCount(1)->and($out['rigs'][0]['no_type'])->toBeTrue();
});
