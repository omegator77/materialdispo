<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $groups = DB::table('items')
            ->select('units_id', 'bezeichnung')
            ->whereNotNull('bezeichnung')
            ->where('bezeichnung', '!=', '')
            ->distinct()
            ->get();

        foreach ($groups as $group) {
            $geraetetypId = DB::table('geraetetypen')->insertGetId([
                'units_id' => $group->units_id,
                'bezeichnung' => $group->bezeichnung,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('items')
                ->where('units_id', $group->units_id)
                ->where('bezeichnung', $group->bezeichnung)
                ->update(['geraetetyp_id' => $geraetetypId]);
        }
    }

    public function down(): void
    {
        DB::table('items')->update(['geraetetyp_id' => null]);
        DB::table('geraetetypen')->truncate();
    }
};
