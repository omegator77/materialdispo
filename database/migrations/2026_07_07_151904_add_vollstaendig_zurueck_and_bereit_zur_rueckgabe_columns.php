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
        Schema::table('vermietvorgaenge', function (Blueprint $table) {
            $table->timestamp('vollstaendig_zurueck_confirmed_at')->nullable();
            $table->foreignId('vollstaendig_zurueck_confirmed_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::table('mietvorgaenge', function (Blueprint $table) {
            $table->timestamp('bereit_zur_rueckgabe_confirmed_at')->nullable();
            $table->foreignId('bereit_zur_rueckgabe_confirmed_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vermietvorgaenge', function (Blueprint $table) {
            $table->dropForeign(['vollstaendig_zurueck_confirmed_by']);
            $table->dropColumn(['vollstaendig_zurueck_confirmed_at', 'vollstaendig_zurueck_confirmed_by']);
        });

        Schema::table('mietvorgaenge', function (Blueprint $table) {
            $table->dropForeign(['bereit_zur_rueckgabe_confirmed_by']);
            $table->dropColumn(['bereit_zur_rueckgabe_confirmed_at', 'bereit_zur_rueckgabe_confirmed_by']);
        });
    }
};
