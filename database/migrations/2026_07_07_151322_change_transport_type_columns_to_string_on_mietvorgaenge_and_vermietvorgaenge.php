<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Transportart wird Freitext statt fester Auswahl — Enum-Constraint
        // entfernen, sonst schlägt jeder Wert außerhalb der alten Optionen
        // mit "Data truncated" fehl.
        Schema::table('mietvorgaenge', function (Blueprint $table) {
            $table->string('transport_type_start')->nullable()->change();
            $table->string('transport_type_end')->nullable()->change();
        });

        Schema::table('vermietvorgaenge', function (Blueprint $table) {
            $table->string('transport_type_start')->nullable()->change();
            $table->string('transport_type_end')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mietvorgaenge', function (Blueprint $table) {
            $table->enum('transport_type_start', ['kurier', 'wir_holen_ab', 'lieferant_liefert'])->nullable()->change();
            $table->enum('transport_type_end', ['kurier', 'wir_bringen_zurueck', 'lieferant_holt_ab'])->nullable()->change();
        });

        Schema::table('vermietvorgaenge', function (Blueprint $table) {
            $table->enum('transport_type_start', ['kurier', 'wir_liefern', 'mieter_holt_ab'])->nullable()->change();
            $table->enum('transport_type_end', ['kurier', 'wir_holen_ab', 'mieter_bringt_zurueck'])->nullable()->change();
        });
    }
};
