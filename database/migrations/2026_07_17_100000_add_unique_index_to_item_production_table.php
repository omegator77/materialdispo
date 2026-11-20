<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * item_production hatte keinen Unique-Index: parallele Requests konnten
 * dasselbe Gerät mehrfach derselben Produktion zuordnen. Vorhandene Duplikate
 * werden vorher entfernt (der älteste Eintrag je Paar bleibt).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Abgeleitete Tabelle, damit MySQL (Fehler 1093) das Löschen aus
        // derselben Tabelle erlaubt.
        DB::table('item_production')
            ->whereNotIn('id', DB::table(
                DB::raw('(select min(id) as id from item_production group by production_id, item_id) as keep_rows')
            )->select('keep_rows.id'))
            ->delete();

        Schema::table('item_production', function (Blueprint $table) {
            $table->unique(['production_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::table('item_production', function (Blueprint $table) {
            $table->dropUnique(['production_id', 'item_id']);
        });
    }
};
