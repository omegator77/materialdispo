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
        Schema::create('camera_details', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('item_id');

            $table->foreign('item_id')
                ->references('id')
                ->on('items')
                ->cascadeOnDelete();

            $table->string('body_serial')->nullable();
            $table->string('fiber_adapter_serial')->nullable();

            $table->enum('large_viewfinder_type', ['OLED', 'LCD'])->nullable();
            $table->string('large_viewfinder_serial')->nullable();

            $table->enum('small_viewfinder_type', ['OLED', 'LCD'])->nullable();
            $table->string('small_viewfinder_serial')->nullable();

            $table->boolean('ssl_license')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('camera_details');
    }
};
