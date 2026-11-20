<?php

use App\Models\Item;
use App\Models\Production;
use App\Models\Unit;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

function itemProductionIndex(array $columns): ?array
{
    return collect(Schema::getIndexes('item_production'))->first(fn ($i) => $i['columns'] === $columns);
}

function itemProductionPair(): array
{
    $unit = Unit::create(['bezeichnung' => 'Kameras']);
    $item = Item::create(['units_id' => $unit->id, 'bezeichnung' => 'Kamera']);
    $production = Production::create([
        'bezeichnung' => 'Produktion',
        'booking_start' => '2026-08-01',
        'booking_end' => '2026-08-02',
    ]);

    return [$item, $production];
}

function insertItemProduction(int $productionId, int $itemId, ?string $notes = null): void
{
    DB::table('item_production')->insert([
        'production_id' => $productionId,
        'item_id' => $itemId,
        'notes' => $notes,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

test('dasselbe Gerät kann nicht zweimal derselben Produktion zugeordnet werden', function () {
    [$item, $production] = itemProductionPair();

    insertItemProduction($production->id, $item->id);

    expect(fn () => insertItemProduction($production->id, $item->id))
        ->toThrow(QueryException::class);
    expect(DB::table('item_production')->count())->toBe(1);
});

test('Unique-Migration entfernt vorhandene Duplikate und behält den ältesten Eintrag', function () {
    [$item, $production] = itemProductionPair();

    $migration = require database_path('migrations/2026_07_17_100000_add_unique_index_to_item_production_table.php');
    $migration->down();
    insertItemProduction($production->id, $item->id, 'erster');
    insertItemProduction($production->id, $item->id, 'doppelt');
    insertItemProduction($production->id, $item->id, 'doppelt 2');

    $migration->up();

    expect(DB::table('item_production')->pluck('notes')->all())->toBe(['erster'])
        ->and(fn () => insertItemProduction($production->id, $item->id))->toThrow(QueryException::class);
});

test('down() entfernt den Unique-Index und behält einen Index auf production_id (Fremdschlüssel)', function () {
    [$item, $production] = itemProductionPair();
    $migration = require database_path('migrations/2026_07_17_100000_add_unique_index_to_item_production_table.php');

    $migration->down();

    expect(itemProductionIndex(['production_id']))->not->toBeNull();

    insertItemProduction($production->id, $item->id);
    insertItemProduction($production->id, $item->id);
    expect(DB::table('item_production')->count())->toBe(2);

    // Wieder herstellbar (Rückweg und erneutes Ausrollen).
    DB::table('item_production')->delete();
    $migration->up();
    expect(itemProductionIndex(['production_id', 'item_id'])['unique'])->toBeTrue();
});

// Diese Tests ändern das Schema (DDL). MySQL committet dabei die Test-Transaktion implizit:
// Daten und Schemaänderungen würden in spätere Tests durchsickern. Darum danach neu aufbauen.
afterEach(function () {
    if (DB::connection()->getDriverName() !== 'sqlite') {
        Artisan::call('migrate:fresh', ['--force' => true]);
    }
});
