<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Symmetrisch zu den Vermieter-FKs: ein Mieter soll nicht mehr löschbar sein,
 * solange ihm noch Vermietvorgänge zugeordnet sind.
 *
 *  - vermietvorgaenge.mieter_id: CASCADE ⇒ RESTRICT
 *      (vorher wurden beim Löschen des Mieters all seine Vermietvorgänge —
 *       und via deren Cascade auch item_vermietvorgang-Zuordnungen und
 *       reminder_logs — lautlos mitgelöscht.)
 *
 * MieterController@destroy fängt die QueryException ab und meldet freundlich.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vermietvorgaenge', function (Blueprint $table) {
            $table->dropForeign(['mieter_id']);
            $table->foreign('mieter_id')->references('id')->on('mieter')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vermietvorgaenge', function (Blueprint $table) {
            $table->dropForeign(['mieter_id']);
            $table->foreign('mieter_id')->references('id')->on('mieter')->cascadeOnDelete();
        });
    }
};
