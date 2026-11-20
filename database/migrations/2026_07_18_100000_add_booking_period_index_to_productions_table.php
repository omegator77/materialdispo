<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Die Verfügbarkeitsprüfung filtert bei jedem Gerät auf den Buchungszeitraum
 * der Produktionen (booking_start <= X AND booking_end >= Y).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productions', function (Blueprint $table) {
            $table->index(['booking_start', 'booking_end']);
        });
    }

    public function down(): void
    {
        Schema::table('productions', function (Blueprint $table) {
            $table->dropIndex(['booking_start', 'booking_end']);
        });
    }
};
