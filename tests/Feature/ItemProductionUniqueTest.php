<?php

use App\Models\Item;
use App\Models\Production;
use App\Models\Unit;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

    Schema::table('item_production', fn ($t) => $t->dropUnique(['production_id', 'item_id']));
    insertItemProduction($production->id, $item->id, 'erster');
    insertItemProduction($production->id, $item->id, 'doppelt');
    insertItemProduction($production->id, $item->id, 'doppelt 2');

    (require database_path('migrations/2026_07_17_100000_add_unique_index_to_item_production_table.php'))->up();

    expect(DB::table('item_production')->pluck('notes')->all())->toBe(['erster'])
        ->and(fn () => insertItemProduction($production->id, $item->id))->toThrow(QueryException::class);
});
