<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->nullable()->after('description');
        });

        // Bestehende Gruppen behalten die heutige alphabetische Reihenfolge,
        // bis sie aktiv per Timeline/Units-Reorder umsortiert werden.
        DB::table('units')->orderBy('bezeichnung')->get()->each(function ($unit, $i) {
            DB::table('units')->where('id', $unit->id)->update(['sort_order' => $i + 1]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
