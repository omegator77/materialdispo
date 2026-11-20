<?php

use App\Models\Item;
use App\Models\Production;
use App\Models\Unit;
use App\Models\User;
use App\Services\ItemAvailabilityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Schema;

function uiProduction(string $name = 'Prod', array $attrs = [], int $items = 1): Production
{
    $unit = Unit::firstOrCreate(['bezeichnung' => 'Kameras']);
    $production = Production::create(array_merge([
        'bezeichnung' => $name, 'booking_start' => '2026-01-10', 'booking_end' => '2026-01-12',
    ], $attrs));

    foreach (range(1, $items) as $i) {
        $production->items()->attach(Item::create(['units_id' => $unit->id, 'bezeichnung' => "{$name} Gerät {$i}"])->id);
    }

    return $production;
}

function queryCount(callable $request): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();
    $request();
    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    return $count;
}

test('Das Dashboard lädt abgeschlossene Produktionen nicht mehr in den Speicher', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $open = uiProduction('Offen');
    foreach (range(1, 8) as $i) {
        uiProduction("Fertig {$i}", ['packvorgang_confirmed_at' => now(), 'packvorgang_confirmed_by' => $admin->id]);
    }

    $geladen = [];
    Event::listen('eloquent.retrieved: '.Production::class, function ($model) use (&$geladen) {
        $geladen[] = $model->bezeichnung;
    });

    $this->actingAs($admin)->get(route('dashboard'))->assertOk()->assertSee('Offen');

    // Erlaubt sind nur die 5 Einträge der Kachel "Letzte Produktionen"; die
    // Liste "Offene Vorgänge" darf die 8 abgeschlossenen nicht zusätzlich laden.
    expect(count(array_filter($geladen, fn ($n) => str_starts_with($n, 'Fertig'))))->toBeLessThanOrEqual(5);
});

test('Produktionen sind nach Buchungszeitraum indiziert', function () {
    expect(Schema::hasIndex('productions', ['booking_start', 'booking_end']))->toBeTrue();
});

test('Die Packvorgang-Seite lädt die Packer ohne N+1-Abfragen', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $production = uiProduction('Pack', items: 6);

    $request = fn () => $this->actingAs($admin)->get(route('packvorgang.show', $production))->assertOk();

    $wenige = queryCount($request);

    foreach ($production->items as $item) {
        $production->itemPacks()->create(['item_id' => $item->id, 'packed_by' => User::factory()->create()->id, 'packed_at' => now()]);
    }
    $production->unsetRelation('itemPacks');

    expect(queryCount($request))->toBeLessThanOrEqual($wenige + 1);
});

test('Namen mit Anführungszeichen brechen den Alpine-Zustand der Packliste nicht auf', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $production = uiProduction('Pack');
    $item = $production->items->first();
    $boese = User::factory()->create(['name' => "x' onfocus='alert(1)"]);
    $production->itemPacks()->create(['item_id' => $item->id, 'packed_by' => $boese->id, 'packed_at' => now()]);

    $html = $this->actingAs($admin)->get(route('packvorgang.show', $production))->assertOk()->getContent();

    expect($html)->not->toContain("onfocus='alert(1)")
        ->and($html)->toContain('x\\u0027 onfocus');
});

test('Flash-Meldungen werden auch auf Produktions- und Geräteliste angezeigt', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    foreach (['productions.index', 'items.index'] as $route) {
        $this->actingAs($admin)->withSession(['success' => 'Erfolg-XYZ', 'warning' => 'Warnung-XYZ', 'error' => 'Fehler-XYZ'])
            ->get(route($route))->assertOk()
            ->assertSee('Erfolg-XYZ')->assertSee('Warnung-XYZ')->assertSee('Fehler-XYZ');
    }
});

test('Die Packliste zeigt Fehler- und Warnmeldungen an', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->withSession(['error' => 'Fehler-XYZ', 'warning' => 'Warnung-XYZ'])
        ->get(route('packvorgang.show', uiProduction('Pack')))
        ->assertSee('Fehler-XYZ')->assertSee('Warnung-XYZ');
});

test('PDF-Dateinamen enthalten keine Pfadtrenner oder Sonderzeichen', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $production = uiProduction('Show/Live: "Süd" ../x');

    $disposition = $this->actingAs($admin)->get(route('productions.pdf', $production->id))
        ->assertOk()->headers->get('content-disposition');

    expect($disposition)->toContain('.pdf')->not->toContain('/')->not->toContain('..')->not->toContain('Süd');
});

test('Ein Fehler beim Hinzufügen von Geräten wird gemeldet statt verschluckt', function () {
    Exceptions::fake();
    $admin = User::factory()->create(['role' => 'admin']);
    $production = uiProduction('Prod');
    $item = Item::first();

    $this->mock(ItemAvailabilityService::class, fn ($m) => $m->shouldReceive('lockItems')->andThrow(new RuntimeException('kaputt')));

    $this->actingAs($admin)->post(route('productions.attachItem', $production->id), ['item_id' => [$item->id]])
        ->assertRedirect()->assertSessionHas('error');

    Exceptions::assertReported(RuntimeException::class);
});
