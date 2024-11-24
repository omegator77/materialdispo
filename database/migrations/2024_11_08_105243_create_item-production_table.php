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
        Schema::create('item_production', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('production_id'); // Fremdschlüssel zu productions
            $table->foreign('production_id')
                    ->references('id')
                    ->on('productions')
                    ->onDelete('cascade');

            $table->unsignedInteger('item_id'); // Fremdschlüssel zu items
            $table->foreign('item_id')
                    ->references('id')
                    ->on('items')
                    ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_prduction', function (Blueprint $table) {
            //
        });
    }
};
