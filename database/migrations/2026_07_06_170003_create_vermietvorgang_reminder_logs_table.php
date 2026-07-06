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
        Schema::create('vermietvorgang_reminder_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('vermietvorgang_id');
            $table->enum('reminder_type', ['start', 'end']);
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->unique(['vermietvorgang_id', 'reminder_type'], 'vermietvorgang_reminder_logs_unique');
        });

        Schema::table('vermietvorgang_reminder_logs', function (Blueprint $table) {
            $table->foreign('vermietvorgang_id')
                ->references('id')
                ->on('vermietvorgaenge')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vermietvorgang_reminder_logs');
    }
};
