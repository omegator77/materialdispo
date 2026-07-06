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
        Schema::create('mailing_list_recipients', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('mailing_list_id');
            $table->string('name')->nullable();
            $table->string('email');
            $table->timestamps();

            $table->unique(['mailing_list_id', 'email']);
        });

        Schema::table('mailing_list_recipients', function (Blueprint $table) {
            $table->foreign('mailing_list_id')
                ->references('id')
                ->on('mailing_lists')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mailing_list_recipients');
    }
};
