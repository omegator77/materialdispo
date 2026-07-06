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
        Schema::create('dry_hires', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('production_id')->unique();
            $table->enum('delivery_type', ['kunde_holt_ab', 'wir_liefern', 'kurier'])->nullable();
            $table->enum('return_type', ['kunde_bringt_zurueck', 'wir_holen_ab', 'kurier'])->nullable();
            $table->string('customer_email')->nullable();
            $table->boolean('notify_customer')->default(false);
            $table->unsignedSmallInteger('reminder_days_before_start')->nullable();
            $table->unsignedSmallInteger('reminder_days_before_end')->nullable();
            $table->unsignedInteger('mailing_list_id')->nullable();
            $table->timestamp('start_confirmed_at')->nullable();
            $table->foreignId('start_confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('end_confirmed_at')->nullable();
            $table->foreignId('end_confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('dry_hires', function (Blueprint $table) {
            $table->foreign('production_id')->references('id')->on('productions')->onDelete('cascade');
            $table->foreign('mailing_list_id')->references('id')->on('mailing_lists')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dry_hires');
    }
};
