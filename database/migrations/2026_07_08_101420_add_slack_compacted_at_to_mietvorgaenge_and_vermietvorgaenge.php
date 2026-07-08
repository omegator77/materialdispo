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
            $table->timestamp('slack_compacted_at')->nullable();
        });

        Schema::table('vermietvorgaenge', function (Blueprint $table) {
            $table->timestamp('slack_compacted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mietvorgaenge', function (Blueprint $table) {
            $table->dropColumn('slack_compacted_at');
        });

        Schema::table('vermietvorgaenge', function (Blueprint $table) {
            $table->dropColumn('slack_compacted_at');
        });
    }
};
