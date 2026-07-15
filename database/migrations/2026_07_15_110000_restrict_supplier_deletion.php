<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ein Vermieter (supplier) soll nicht mehr löschbar sein, solange ihm noch
 * Geräte oder Mietvorgänge zugeordnet sind — statt die Daten still zu
 * verändern/löschen:
 *
 *  - items.suppliers_id:        SET NULL ⇒ RESTRICT
 *      (vorher wurden gemietete Geräte beim Löschen des Vermieters lautlos zu
 *       „Eigentum" umgewidmet — sinnfrei.)
 *  - mietvorgaenge.suppliers_id: CASCADE ⇒ RESTRICT
 *      (vorher wurden alle Mietvorgänge des Vermieters lautlos mitgelöscht.)
 *
 * SupplierController@destroy fängt die daraus resultierende QueryException ab
 * und meldet freundlich.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropForeign(['suppliers_id']);
            $table->foreign('suppliers_id')->references('id')->on('suppliers')->restrictOnDelete();
        });

        Schema::table('mietvorgaenge', function (Blueprint $table) {
            $table->dropForeign(['suppliers_id']);
            $table->foreign('suppliers_id')->references('id')->on('suppliers')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropForeign(['suppliers_id']);
            $table->foreign('suppliers_id')->references('id')->on('suppliers')->nullOnDelete();
        });

        Schema::table('mietvorgaenge', function (Blueprint $table) {
            $table->dropForeign(['suppliers_id']);
            $table->foreign('suppliers_id')->references('id')->on('suppliers')->cascadeOnDelete();
        });
    }
};
