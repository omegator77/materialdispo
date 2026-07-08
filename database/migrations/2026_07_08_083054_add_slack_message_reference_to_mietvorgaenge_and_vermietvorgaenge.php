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
            $table->string('slack_channel')->nullable();
            $table->string('slack_message_ts')->nullable();
        });

        Schema::table('vermietvorgaenge', function (Blueprint $table) {
            $table->string('slack_channel')->nullable();
            $table->string('slack_message_ts')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mietvorgaenge', function (Blueprint $table) {
            $table->dropColumn(['slack_channel', 'slack_message_ts']);
        });

        Schema::table('vermietvorgaenge', function (Blueprint $table) {
            $table->dropColumn(['slack_channel', 'slack_message_ts']);
        });
    }
};
