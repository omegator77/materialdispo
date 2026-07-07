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
            $table->timestamp('gerichtet_confirmed_at')->nullable();
            $table->foreignId('gerichtet_confirmed_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::table('mietvorgaenge', function (Blueprint $table) {
            $table->timestamp('kontrolliert_confirmed_at')->nullable();
            $table->foreignId('kontrolliert_confirmed_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vermietvorgaenge', function (Blueprint $table) {
            $table->dropForeign(['gerichtet_confirmed_by']);
            $table->dropColumn(['gerichtet_confirmed_at', 'gerichtet_confirmed_by']);
        });

        Schema::table('mietvorgaenge', function (Blueprint $table) {
            $table->dropForeign(['kontrolliert_confirmed_by']);
            $table->dropColumn(['kontrolliert_confirmed_at', 'kontrolliert_confirmed_by']);
        });
    }
};
