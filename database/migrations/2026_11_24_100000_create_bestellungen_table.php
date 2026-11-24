<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bestellungen', function (Blueprint $table) {
            $table->id();
            $table->string('bestellt_bei');
            $table->text('bestellt_was');
            $table->string('bezahlt_mit');
            $table->dateTime('bestellt_am');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('versanddienstleister', 20)->nullable();
            $table->string('sendungsnummer')->nullable();
            $table->timestamp('rechnung_vorhanden_at')->nullable();
            $table->foreignId('rechnung_vorhanden_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('abgelegt_at')->nullable();
            $table->foreignId('abgelegt_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('letzte_erinnerung_at')->nullable();
            $table->string('slack_channel')->nullable();
            $table->string('slack_message_ts')->nullable();
            $table->string('slack_view_id')->nullable()->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bestellungen');
    }
};
