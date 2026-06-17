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
        Schema::create('lens_details', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('item_id');

            $table->foreign('item_id')
                ->references('id')
                ->on('items')
                ->cascadeOnDelete();

            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('zoom_factor')->nullable();

            $table->string('zoom_servo_model')->nullable();
            $table->string('zoom_servo_serial_number')->nullable();

            $table->string('focus_servo_model')->nullable();
            $table->string('focus_servo_serial_number')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lens_details');
    }
};
