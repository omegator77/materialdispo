<?php

use App\Models\Item;
use App\Models\Mieter;
use App\Models\Mietvorgang;
use App\Models\Production;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Vermietvorgang;

/**
 * Regressionstest für den ursprünglichen Bug: items.vermietvorgang_id war ein
 * einzelner Fremdschlüssel, daher hat das Zuordnen zu einem zweiten,
 * zeitlich getrennten Vermietvorgang die Zuordnung zum ersten stillschweigend
 * überschrieben. Seit dem Umbau auf item_mietvorgang/item_vermietvorgang
 * (Pivot-Tabellen, analog zu item_production) kann ein Gerät mehrere
 * gleichzeitige, sich nicht überschneidende Zuordnungen halten.
 */
test('ein Gerät kann drei sequenzielle, nicht überlappende Vermietvorgänge gleichzeitig halten', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $unit = Unit::create(['bezeichnung' => 'Stück']);
    $item = Item::create(['bezeichnung' => 'Kamera X', 'units_id' => $unit->id]);

    $mieter1 = Mieter::create(['bezeichnung' => 'Kunde 1']);
    $mieter2 = Mieter::create(['bezeichnung' => 'Kunde 2']);
    $mieter3 = Mieter::create(['bezeichnung' => 'Kunde 3']);

    $v1 = Vermietvorgang::create(['mieter_id' => $mieter1->id, 'rent_start' => '2026-10-12', 'rent_end' => '2026-10-14']);
    $v2 = Vermietvorgang::create(['mieter_id' => $mieter2->id, 'rent_start' => '2026-10-20', 'rent_end' => '2026-10-22']);
    $v3 = Vermietvorgang::create(['mieter_id' => $mieter3->id, 'rent_start' => '2026-10-28', 'rent_end' => '2026-10-30']);

    foreach ([$v1, $v2, $v3] as $v) {
        $this->actingAs($user)->post(route('vermietvorgaenge.attachItems', $v), [
            'item_id' => [$item->id],
        ])->assertSessionHas('success');
    }

    expect($item->vermietvorgaenge()->count())->toBe(3);
    expect($v1->fresh()->items()->count())->toBe(1);
    expect($v2->fresh()->items()->count())->toBe(1);
    expect($v3->fresh()->items()->count())->toBe(1);
});

test('dasselbe gilt symmetrisch für Mietvorgänge', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $unit = Unit::create(['bezeichnung' => 'Stück']);
    $supplier = Supplier::create(['bezeichnung' => 'Verleiher']);
    $item = Item::create(['bezeichnung' => 'Kamera Y', 'units_id' => $unit->id, 'suppliers_id' => $supplier->id]);

    $m1 = Mietvorgang::create(['suppliers_id' => $supplier->id, 'rent_start' => '2026-10-01', 'rent_end' => '2026-10-05']);
    $m2 = Mietvorgang::create(['suppliers_id' => $supplier->id, 'rent_start' => '2026-10-10', 'rent_end' => '2026-10-15']);

    foreach ([$m1, $m2] as $m) {
        $this->actingAs($user)->post(route('mietvorgaenge.attachItems', $m), [
            'item_id' => [$item->id],
        ])->assertSessionHas('success');
    }

    expect($item->mietvorgaenge()->count())->toBe(2);
});

test('überlappende, noch offene Vermietvorgänge blockieren sich weiterhin gegenseitig', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $unit = Unit::create(['bezeichnung' => 'Stück']);
    $item = Item::create(['bezeichnung' => 'Kamera Z', 'units_id' => $unit->id]);

    $mieterA = Mieter::create(['bezeichnung' => 'Kunde A']);
    $mieterB = Mieter::create(['bezeichnung' => 'Kunde B']);

    $vA = Vermietvorgang::create(['mieter_id' => $mieterA->id, 'rent_start' => '2026-11-01', 'rent_end' => '2026-11-10']);
    $vB = Vermietvorgang::create(['mieter_id' => $mieterB->id, 'rent_start' => '2026-11-05', 'rent_end' => '2026-11-15']);

    $this->actingAs($user)->post(route('vermietvorgaenge.attachItems', $vA), ['item_id' => [$item->id]])
        ->assertSessionHas('success');

    $this->actingAs($user)->post(route('vermietvorgaenge.attachItems', $vB), ['item_id' => [$item->id]])
        ->assertSessionHas('error');

    expect($item->vermietvorgaenge()->count())->toBe(1);
    expect($item->vermietvorgaenge()->first()->id)->toBe($vA->id);
});

test('ein abgeschlossener Vermietvorgang blockiert trotz Datumsüberlappung nicht mehr', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $unit = Unit::create(['bezeichnung' => 'Stück']);
    $item = Item::create(['bezeichnung' => 'Kamera W', 'units_id' => $unit->id]);

    $mieterA = Mieter::create(['bezeichnung' => 'Kunde A']);
    $mieterB = Mieter::create(['bezeichnung' => 'Kunde B']);

    $vA = Vermietvorgang::create([
        'mieter_id' => $mieterA->id,
        'rent_start' => '2026-11-01',
        'rent_end' => '2026-11-10',
        'transport_end_confirmed_at' => now(),
        'transport_end_confirmed_by' => $user->id,
        'vollstaendig_zurueck_confirmed_at' => now(),
        'vollstaendig_zurueck_confirmed_by' => $user->id,
    ]);
    $vB = Vermietvorgang::create(['mieter_id' => $mieterB->id, 'rent_start' => '2026-11-05', 'rent_end' => '2026-11-15']);

    $vA->items()->attach($item->id);

    expect($vA->isComplete())->toBeTrue();

    $this->actingAs($user)->post(route('vermietvorgaenge.attachItems', $vB), ['item_id' => [$item->id]])
        ->assertSessionHas('success');

    expect($item->fresh()->vermietvorgaenge()->pluck('vermietvorgaenge.id')->sort()->values()->all())
        ->toBe([$vA->id, $vB->id]);
});

test('Entfernen aus einem Vermietvorgang lässt andere Zuordnungen desselben Geräts unberührt', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $unit = Unit::create(['bezeichnung' => 'Stück']);
    $item = Item::create(['bezeichnung' => 'Kamera V', 'units_id' => $unit->id]);

    $mieter1 = Mieter::create(['bezeichnung' => 'Kunde 1']);
    $mieter2 = Mieter::create(['bezeichnung' => 'Kunde 2']);

    $v1 = Vermietvorgang::create(['mieter_id' => $mieter1->id, 'rent_start' => '2026-12-01', 'rent_end' => '2026-12-05']);
    $v2 = Vermietvorgang::create(['mieter_id' => $mieter2->id, 'rent_start' => '2026-12-10', 'rent_end' => '2026-12-15']);

    $v1->items()->attach($item->id);
    $v2->items()->attach($item->id);

    $this->actingAs($user)
        ->delete(route('vermietvorgaenge.detachItem', [$v1, $item]))
        ->assertSessionHas('success');

    expect($item->vermietvorgaenge()->count())->toBe(1);
    expect($item->vermietvorgaenge()->first()->id)->toBe($v2->id);
});

test('die Schnellzuordnung über das Geräte-Formular nutzt denselben zentralen Konfliktschutz wie der Picker', function () {
    $unit = Unit::create(['bezeichnung' => 'Stück']);
    $supplier = Supplier::create(['bezeichnung' => 'Verleiher']);
    $item = Item::create(['bezeichnung' => 'Kamera U', 'units_id' => $unit->id, 'suppliers_id' => $supplier->id]);

    $result1 = $item->syncMietvorgang('2026-10-01', '2026-10-10');
    expect($result1['added'])->toBeTrue();

    // Überlappender Zeitraum, noch offener Mietvorgang -> muss blockiert werden.
    $result2 = $item->syncMietvorgang('2026-10-05', '2026-10-15');
    expect($result2['added'])->toBeFalse();
    expect($result2['reason'])->not->toBeNull();

    // Nicht überlappender Zeitraum -> zusätzliche Zuordnung wird angelegt.
    $result3 = $item->syncMietvorgang('2026-11-01', '2026-11-10');
    expect($result3['added'])->toBeTrue();

    expect($item->mietvorgaenge()->count())->toBe(2);
});

test('Mietvorgang mit exakt gleichem Zeitraum wie die Produktion deckt sie ab (Grenzfall-Regression)', function () {
    // Bug: $mv->rent_start (Carbon) wurde direkt mit $production->booking_start
    // (roher DB-String) verglichen. PHP vergleicht Objekt<=>String dabei über
    // __toString() lexikographisch ("2026-07-20 00:00:00" vs. "2026-07-20") —
    // bei exakt gleichem Datum "verliert" der Carbon-Wert durch den
    // Zeit-Suffix, obwohl die Daten identisch sind. Nur an der exakten Grenze
    // sichtbar, deshalb bei abweichenden Testdaten lange unbemerkt geblieben.
    $user = User::factory()->create(['role' => 'admin']);
    $unit = Unit::create(['bezeichnung' => 'Kameras']);
    $supplier = Supplier::create(['bezeichnung' => 'Verleiher']);
    $item = Item::create(['bezeichnung' => 'Testgerät', 'units_id' => $unit->id, 'suppliers_id' => $supplier->id]);

    $mietvorgang = Mietvorgang::create(['suppliers_id' => $supplier->id, 'rent_start' => '2026-07-20', 'rent_end' => '2026-07-22']);
    $this->actingAs($user)->post(route('mietvorgaenge.attachItems', $mietvorgang), ['item_id' => [$item->id]])
        ->assertSessionHas('success');

    $production = Production::create(['bezeichnung' => 'Produktion exakt', 'booking_start' => '2026-07-20', 'booking_end' => '2026-07-22']);

    $response = $this->actingAs($user)->post(route('productions.attachItem', $production->id), ['item_id' => [$item->id]]);
    $response->assertSessionHas('success');

    expect($production->fresh()->items->contains($item->id))->toBeTrue();
});
