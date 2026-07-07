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
        Schema::table('mietvorgaenge', function (Blueprint $table) {
            $table->timestamp('transport_start_confirmed_at')->nullable();
            $table->foreignId('transport_start_confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('transport_end_confirmed_at')->nullable();
            $table->foreignId('transport_end_confirmed_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mietvorgaenge', function (Blueprint $table) {
            $table->dropConstrainedForeignId('transport_start_confirmed_by');
            $table->dropConstrainedForeignId('transport_end_confirmed_by');
            $table->dropColumn(['transport_start_confirmed_at', 'transport_end_confirmed_at']);
        });
    }
};
