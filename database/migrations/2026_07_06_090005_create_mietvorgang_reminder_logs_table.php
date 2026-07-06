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
        Schema::create('mietvorgang_reminder_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('mietvorgang_id');
            $table->enum('reminder_type', ['start', 'end']);
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->unique(['mietvorgang_id', 'reminder_type']);
        });

        Schema::table('mietvorgang_reminder_logs', function (Blueprint $table) {
            $table->foreign('mietvorgang_id')
                ->references('id')
                ->on('mietvorgaenge')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mietvorgang_reminder_logs');
    }
};
