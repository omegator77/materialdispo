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
        // 'kontakt'/'phone' sind in beiden Requests (Supplier/MieterRequest)
        // als 'nullable' validiert, waren aber in den Tabellen NOT NULL —
        // führte zu einem Integrity-Constraint-Fehler beim Anlegen ohne
        // diese Felder.
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('kontakt')->nullable()->change();
            $table->string('phone')->nullable()->change();
        });

        Schema::table('mieter', function (Blueprint $table) {
            $table->string('kontakt')->nullable()->change();
            $table->string('phone')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('kontakt')->nullable(false)->change();
            $table->string('phone')->nullable(false)->change();
        });

        Schema::table('mieter', function (Blueprint $table) {
            $table->string('kontakt')->nullable(false)->change();
            $table->string('phone')->nullable(false)->change();
        });
    }
};
