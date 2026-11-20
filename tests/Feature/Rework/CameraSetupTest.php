<?php

use App\Models\CameraConfig;
use App\Models\Geraetetyp;
use App\Models\Item;
use App\Models\Production;
use App\Models\Unit;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'user']);
    // Die App erkennt Gruppen an festen IDs (1 = Kameras, 2 = Objektive): explizit anlegen,
    // damit der Test nicht davon abhängt, dass der ID-Zähler bei 1 beginnt (MySQL macht das nicht).
    $this->units = collect(['Kameras', 'Optiken', 'Stativ', 'Köpfe'])->values()
        ->mapWithKeys(fn ($n, $i) => [$n => Unit::forceCreate(['id' => $i + 1, 'bezeichnung' => $n])]);
    $typ = fn ($unit, $name) => Geraetetyp::create(['units_id' => $this->units[$unit]->id, 'bezeichnung' => $name]);
    $this->camT = $typ('Kameras', 'LDX');
    $this->lensT = $typ('Optiken', '22fach');
    $this->lensT2 = $typ('Optiken', '14fach');
    $this->tripT = $typ('Stativ', 'Sachtler');
    $this->headT = $typ('Köpfe', 'Vinten Kopf');

    $this->mk = fn ($t, $unit, $nr) => Item::create(['units_id' => $this->units[$unit]->id, 'geraetetyp_id' => $t->id, 'bezeichnung' => $t->bezeichnung, 'nummer' => (string) $nr]);

    $this->past = Production::create(['bezeichnung' => 'Alt', 'booking_start' => '2026-01-10', 'booking_end' => '2026-01-12']);
    $this->prod = Production::create(['bezeichnung' => 'Neu', 'booking_start' => '2026-12-10', 'booking_end' => '2026-12-12']);
});

function pastRig($t, $past, int $n, $lensT, $tripT, $headT)
{
    $cam = ($t->mk)($t->camT, 'Kameras', 90 + $n);
    CameraConfig::create([
        'production_id' => $past->id, 'cam_number' => "Cam {$n}", 'item_id' => $cam->id,
        'lens' => ($t->mk)($lensT, 'Optiken', 90 + $n)->id,
        'tripod' => ($t->mk)($tripT, 'Stativ', 90 + $n)->id,
        'tripod_head' => $headT ? ($t->mk)($headT, 'Köpfe', 90 + $n)->id : null,
    ]);
}

test('auto rigs copy the most frequent past setup per slot, reuse the last devices and never double-assign', function () {
    foreach ([1, 2, 3] as $n) {
        pastRig($this, $this->past, $n, $n === 3 ? $this->lensT2 : $this->lensT, $this->tripT, $this->headT);
    }
    ($this->mk)($this->camT, 'Kameras', 1);
    ($this->mk)($this->camT, 'Kameras', 2);
    ($this->mk)($this->lensT, 'Optiken', 1);
    ($this->mk)($this->tripT, 'Stativ', 1);
    ($this->mk)($this->headT, 'Köpfe', 1);

    $this->actingAs($this->user)
        ->post(route('camera-config.auto', $this->prod), ['geraetetyp_id' => $this->camT->id, 'anzahl' => 2])
        ->assertSessionHas('success');

    $configs = CameraConfig::where('production_id', $this->prod->id)->orderBy('cam_number')->get();
    expect($configs->pluck('cam_number')->all())->toBe(['Cam 1', 'Cam 2']);

    foreach (['lens' => $this->lensT, 'tripod' => $this->tripT, 'tripod_head' => $this->headT] as $slot => $typ) {
        $ids = $configs->pluck($slot)->all();
        expect($ids)->each->not->toBeNull()
            ->and(array_unique($ids))->toHaveCount(2); // nie dasselbe Gerät zweimal
        expect(Item::whereIn('id', $ids)->pluck('geraetetyp_id')->unique()->all())->toBe([$typ->id]); // 14fach (Minderheit) wird nicht gewählt
    }
});

test('numbering continues after existing cam numbers and devices are never reused', function () {
    $c1 = ($this->mk)($this->camT, 'Kameras', 1);
    ($this->mk)($this->camT, 'Kameras', 2);
    CameraConfig::create(['production_id' => $this->prod->id, 'cam_number' => 'Cam 4', 'item_id' => $c1->id]);

    $this->actingAs($this->user)->post(route('camera-config.auto', $this->prod), ['geraetetyp_id' => $this->camT->id, 'anzahl' => 3])
        ->assertSessionHas('success');

    $configs = CameraConfig::where('production_id', $this->prod->id)->orderBy('cam_number')->get();
    expect($configs->pluck('cam_number')->all())->toBe(['Cam 4', 'Cam 5']); // nur eine freie Kamera übrig
    expect(session('warning'))->toContain('2 Kamera(s) nicht verfügbar');
});

test('without history rigs are created camera only and the user is told', function () {
    ($this->mk)($this->camT, 'Kameras', 1);

    $this->actingAs($this->user)->post(route('camera-config.auto', $this->prod), ['geraetetyp_id' => $this->camT->id, 'anzahl' => 1])
        ->assertSessionHas('success');

    $c = CameraConfig::where('production_id', $this->prod->id)->first();
    expect($c->lens)->toBeNull()->and($c->tripod)->toBeNull();
});

test('a busy camera is not chosen', function () {
    $busy = ($this->mk)($this->camT, 'Kameras', 1);
    $other = Production::create(['bezeichnung' => 'Parallel', 'booking_start' => '2026-12-11', 'booking_end' => '2026-12-13']);
    $other->items()->attach($busy->id);

    $this->actingAs($this->user)->post(route('camera-config.auto', $this->prod), ['geraetetyp_id' => $this->camT->id, 'anzahl' => 1])
        ->assertSessionHas('error');

    expect(CameraConfig::where('production_id', $this->prod->id)->count())->toBe(0);
});

test('the create form is prefilled with the learned setup for the chosen camera', function () {
    pastRig($this, $this->past, 1, $this->lensT, $this->tripT, $this->headT);
    $cam = ($this->mk)($this->camT, 'Kameras', 1);
    $lens = Item::find(CameraConfig::first()->lens); // zuletzt verwendetes Objektiv wird bevorzugt

    $html = $this->actingAs($this->user)
        ->get(route('camera-config.create', ['production' => $this->prod, 'camera_item_id' => $cam->id]))
        ->assertOk()->getContent();

    expect($html)->toMatch('/<option value="'.$lens->id.'"\s+selected/');
});

test('viewers cannot create rigs automatically', function () {
    $viewer = User::factory()->create(['role' => 'viewer']);
    $this->actingAs($viewer)->post(route('camera-config.auto', $this->prod), [])->assertForbidden();
});
