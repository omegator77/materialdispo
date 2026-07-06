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
        Schema::create('vermietvorgaenge', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('mieter_id');
            $table->date('rent_start');
            $table->date('rent_end');
            $table->enum('transport_type_start', ['kurier', 'wir_liefern', 'mieter_holt_ab'])->nullable();
            $table->enum('transport_type_end', ['kurier', 'wir_holen_ab', 'mieter_bringt_zurueck'])->nullable();
            $table->boolean('notify_mieter')->default(false);
            $table->unsignedSmallInteger('reminder_days_before_start')->nullable();
            $table->unsignedSmallInteger('reminder_days_before_end')->nullable();
            $table->unsignedInteger('mailing_list_id')->nullable();
            $table->timestamp('transport_start_confirmed_at')->nullable();
            $table->foreignId('transport_start_confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('transport_end_confirmed_at')->nullable();
            $table->foreignId('transport_end_confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['mieter_id', 'rent_start', 'rent_end']);
        });

        Schema::table('vermietvorgaenge', function (Blueprint $table) {
            $table->foreign('mieter_id')
                ->references('id')
                ->on('mieter')
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
        Schema::dropIfExists('vermietvorgaenge');
    }
};
