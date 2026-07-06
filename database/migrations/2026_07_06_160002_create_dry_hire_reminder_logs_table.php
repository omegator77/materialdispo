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
        Schema::create('dry_hire_reminder_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('dry_hire_id');
            $table->enum('reminder_type', ['start', 'end']);
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->unique(['dry_hire_id', 'reminder_type']);
        });

        Schema::table('dry_hire_reminder_logs', function (Blueprint $table) {
            $table->foreign('dry_hire_id')->references('id')->on('dry_hires')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dry_hire_reminder_logs');
    }
};
