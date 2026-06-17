<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitor_details', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('item_id');

            $table->foreign('item_id')
                ->references('id')
                ->on('items')
                ->cascadeOnDelete();

            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();

            $table->string('screen_size')->nullable();

            $table->boolean('has_speakers')->default(false);
            $table->boolean('has_headphone')->default(false);

            $table->string('converter_number')->nullable();
            $table->string('converter_model')->nullable();
            $table->boolean('converter_audio')->default(false);

            $table->string('max_input_format')->nullable();

            $table->boolean('has_stand')->default(false);
            $table->string('stand_number')->nullable();

            $table->timestamps();

            $table->unique('item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitor_details');
    }
};
