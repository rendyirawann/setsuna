<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Log scan QR, dipakai untuk statistik berapa tamu yang membuka kamera. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_scans', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_id');
            $table->uuid('event_guest_id')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('device')->nullable();
            $table->string('platform')->nullable();
            $table->string('browser')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('event_id')->references('id')->on('events')->cascadeOnDelete();
            $table->foreign('event_guest_id')->references('id')->on('event_guests')->nullOnDelete();
            $table->index(['event_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_scans');
    }
};
