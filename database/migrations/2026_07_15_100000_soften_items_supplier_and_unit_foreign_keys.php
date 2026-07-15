<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Entschärft zwei gefährliche ON DELETE CASCADE auf `items`:
 *
 *  - items.suppliers_id -> suppliers: CASCADE ⇒ SET NULL
 *      Ein gelöschter Vermieter machte bisher alle seine Geräte lautlos platt.
 *      Jetzt wird das Gerät stattdessen zu „Eigentum" (suppliers_id = NULL).
 *
 *  - items.units_id -> units: CASCADE ⇒ RESTRICT
 *      Eine gelöschte Gruppe riss bisher alle Geräte der Gruppe mit.
 *      Jetzt lässt sich eine Gruppe nicht mehr löschen, solange ihr Geräte
 *      zugeordnet sind (UnitController fängt das mit einer Meldung ab).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropForeign(['suppliers_id']);
            $table->dropForeign(['units_id']);
        });

        Schema::table('items', function (Blueprint $table) {
            $table->foreign('suppliers_id')->references('id')->on('suppliers')->nullOnDelete();
            $table->foreign('units_id')->references('id')->on('units')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropForeign(['suppliers_id']);
            $table->dropForeign(['units_id']);
        });

        Schema::table('items', function (Blueprint $table) {
            $table->foreign('suppliers_id')->references('id')->on('suppliers')->cascadeOnDelete();
            $table->foreign('units_id')->references('id')->on('units')->cascadeOnDelete();
        });
    }
};
