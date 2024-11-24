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
        Schema::create('items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('units_id'); // Fremdschlüssel zu units
            $table->unsignedInteger('suppliers_id')->nullable(); // Fremdschlüssel zu suppliers
            $table->string('bezeichnung');
            $table->string('description')->nullable();
            $table->integer('quantiy')->nullable();
            $table->boolean('is_rented');
            $table->date('rent_start')->nullable();
            $table->date('rent_end')->nullable();        
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
