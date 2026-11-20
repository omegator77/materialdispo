<?php

use App\Models\CameraConfig;
use App\Models\Geraetetyp;
use App\Models\Item;
use App\Models\Production;
use App\Models\Unit;
use App\Models\VbProtokoll;
use App\Services\SlackVorgangSync;
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
    $this->prod = Production::create(['bezeichnung' => 'Neu', 'booking_start' => '2026-12-10', 'booking_end' => '2026-12-12']);
    $this->vb = VbProtokoll::create(['production_id' => $this->prod->id]);

    $this->apply = fn (array $auswahl) => app(VbVorschlagService::class)->apply($this->prod->fresh(), $auswahl);
    $this->rig = fn (Item $cam, ?Item $lens = null, bool $take = true) => [
        'take' => $take, 'camera' => $cam->id, 'lens' => $lens?->id,
        'tripod' => null, 'tripod_head' => null, 'large_lens_adapter' => null,
    ];
});

test('übernimmt gewählte Züge und Material', function () {
    $c1 = ($this->mk)($this->camT, '1');
    $c2 = ($this->mk)($this->camT, '2');
    $l1 = ($this->mk)($this->lensT, '1');
    $e1 = ($this->mk)(($this->typ)('Kabel'), '1');
    $e2 = ($this->mk)(($this->typ)('Monitor'), '1');
    $this->mock(SlackVorgangSync::class)->shouldReceive('syncProduction')->once();

    $out = ($this->apply)(['rigs' => [($this->rig)($c1, $l1), ($this->rig)($c2)], 'items' => [$e1->id, $e2->id]]);

    expect($out['created'])->toHaveCount(2)
        ->and($out['attached'])->toHaveCount(2)
        ->and(CameraConfig::where('production_id', $this->prod->id)->orderBy('id')->pluck('cam_number')->all())->toBe(['Cam 1', 'Cam 2'])
        ->and(DB::table('item_production')->where('production_id', $this->prod->id)->count())->toBe(2);
});

test('take false oder fehlende Kamera wird ausgelassen', function () {
    $c1 = ($this->mk)($this->camT, '1');
    $c2 = ($this->mk)($this->camT, '2');
    $this->mock(SlackVorgangSync::class)->shouldReceive('syncProduction')->once();

    $out = ($this->apply)(['rigs' => [
        ($this->rig)($c1, null, false),
        ['take' => true, 'camera' => null, 'lens' => null, 'tripod' => null, 'tripod_head' => null, 'large_lens_adapter' => null],
        ($this->rig)($c2),
    ], 'items' => []]);

    expect($out['created'])->toHaveCount(1)
        ->and(CameraConfig::where('production_id', $this->prod->id)->count())->toBe(1)
        ->and(CameraConfig::first()->item_id)->toBe($c2->id);
});

test('Zug-Namen werden bei der Übernahme vergeben, nicht aus der Vorschau', function () {
    $c1 = ($this->mk)($this->camT, '1');
    $c2 = ($this->mk)($this->camT, '2');
    $this->mock(SlackVorgangSync::class)->shouldReceive('syncProduction');
    $this->vb->anforderungen()->create(['cam_number' => '1', 'geraetetyp_id' => $this->camT->id, 'anzahl' => 1]);

    expect(app(VbVorschlagService::class)->propose($this->prod->fresh())['rigs'][0]['cam_label'])->toBe('Cam 1');
    CameraConfig::create(['production_id' => $this->prod->id, 'item_id' => $c1->id, 'cam_number' => 'Cam 1']);

    $out = ($this->apply)(['rigs' => [($this->rig)($c2)], 'items' => []]);

    expect($out['created'][0]->cam_number)->toBe('Cam 2');
});

test('Gerät inzwischen vergeben ⇒ Teil wird übersprungen, Rest übernommen', function () {
    $c1 = ($this->mk)($this->camT, '1');
    $c2 = ($this->mk)($this->camT, '2');
    $l1 = ($this->mk)($this->lensT, '1');
    $e1 = ($this->mk)(($this->typ)('Kabel'), '1');
    $this->mock(SlackVorgangSync::class)->shouldReceive('syncProduction')->once();

    $other = Production::create(['bezeichnung' => 'Andere', 'booking_start' => '2026-12-11', 'booking_end' => '2026-12-13']);
    $other->items()->attach($l1->id);

    $out = ($this->apply)(['rigs' => [($this->rig)($c1, $l1), ($this->rig)($c2)], 'items' => [$e1->id]]);

    expect($out['created'])->toHaveCount(1)
        ->and($out['attached'])->toHaveCount(1)
        ->and($out['skipped'])->toHaveCount(1)
        ->and($out['skipped'][0])->toContain($l1->bezeichnung)
        ->and(CameraConfig::where('production_id', $this->prod->id)->count())->toBe(1)
        ->and(CameraConfig::first()->item_id)->toBe($c2->id)
        ->and(CameraConfig::first()->cam_number)->toBe('Cam 1');
});

test('dasselbe Gerät in zwei Zügen derselben Übernahme', function () {
    $c1 = ($this->mk)($this->camT, '1');
    $this->mock(SlackVorgangSync::class)->shouldReceive('syncProduction')->once();

    $out = ($this->apply)(['rigs' => [($this->rig)($c1), ($this->rig)($c1)], 'items' => []]);

    expect($out['created'])->toHaveCount(1)
        ->and($out['skipped'])->toHaveCount(1)
        ->and($out['skipped'][0])->not->toBe('');
});

test('zweimal übernehmen verdoppelt nichts', function () {
    $c1 = ($this->mk)($this->camT, '1');
    $e1 = ($this->mk)(($this->typ)('Kabel'), '1');
    $this->mock(SlackVorgangSync::class)->shouldReceive('syncProduction')->once();
    $auswahl = ['rigs' => [($this->rig)($c1)], 'items' => [$e1->id]];

    ($this->apply)($auswahl);
    $second = ($this->apply)($auswahl);

    expect($second['created'])->toBeEmpty()
        ->and($second['attached'])->toBeEmpty()
        ->and($second['skipped'])->not->toBeEmpty()
        ->and(CameraConfig::where('production_id', $this->prod->id)->count())->toBe(1)
        ->and(DB::table('item_production')->where('production_id', $this->prod->id)->count())->toBe(1)
        ->and(app(VbVorschlagService::class)->propose($this->prod->fresh())['empty'])->toBeTrue();
});

test('Slack wird nicht synchronisiert, wenn nichts übernommen wurde', function () {
    $this->mock(SlackVorgangSync::class)->shouldNotReceive('syncProduction');

    $out = ($this->apply)(['rigs' => [], 'items' => []]);

    expect($out['created'])->toBeEmpty()->and($out['attached'])->toBeEmpty();
});
