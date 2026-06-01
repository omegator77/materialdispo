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
        Schema::create('camera_configs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('production_id'); // Referenz auf die Produktion
            $table->unsignedInteger('item_id'); // Referenz auf die Kamera (Item)
            $table->string('cam_number'); // Kameranummer
            $table->string('cam_position')->nullable(); // Kameraposition
            $table->string('lens')->nullable(); // Objektiv
            $table->string('tripod')->nullable(); // Stativ
            $table->string('tripod_head')->nullable(); // Stativkopf
            $table->string('large_lens_adapter')->nullable(); // Großer Objektivadapter
            $table->text('notes')->nullable(); // Notizen
            $table->timestamps();
        
            $table->foreign('production_id')->references('id')->on('productions')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('camera_configs');
    }
};
