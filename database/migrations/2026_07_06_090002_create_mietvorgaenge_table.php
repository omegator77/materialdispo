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
        Schema::create('mietvorgaenge', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('suppliers_id');
            $table->date('rent_start');
            $table->date('rent_end');
            $table->enum('transport_type_start', ['kurier', 'wir_holen_ab', 'lieferant_liefert'])->nullable();
            $table->enum('transport_type_end', ['kurier', 'wir_bringen_zurueck', 'lieferant_holt_ab'])->nullable();
            $table->boolean('notify_supplier')->default(false);
            $table->unsignedSmallInteger('reminder_days_before_start')->nullable();
            $table->unsignedSmallInteger('reminder_days_before_end')->nullable();
            $table->unsignedInteger('mailing_list_id')->nullable();
            $table->timestamps();

            $table->index(['suppliers_id', 'rent_start', 'rent_end']);
        });

        Schema::table('mietvorgaenge', function (Blueprint $table) {
            $table->foreign('suppliers_id')
                ->references('id')
                ->on('suppliers')
                ->onDelete('cascade');

            $table->foreign('mailing_list_id')
                ->references('id')
                ->on('mailing_lists')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mietvorgaenge');
    }
};
