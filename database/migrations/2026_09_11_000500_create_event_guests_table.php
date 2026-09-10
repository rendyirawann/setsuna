<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tamu undangan yang scan QR. Diidentifikasi lewat token di cookie
 * perangkat, tanpa login. Kuota disalin dari event supaya admin bisa
 * menambah jatah satu tamu tanpa mengubah setelan event.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_guests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('event_id');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->string('color', 20)->nullable();

            $table->unsignedSmallInteger('photo_quota')->default(18);
            $table->unsignedSmallInteger('video_quota')->default(2);
            $table->unsignedSmallInteger('boomerang_quota')->default(1);

            $table->unsignedSmallInteger('photos_used')->default(0);
            $table->unsignedSmallInteger('videos_used')->default(0);
            $table->unsignedSmallInteger('boomerangs_used')->default(0);

            $table->boolean('is_blocked')->default(false);
            $table->string('ip', 45)->nullable();
            $table->string('device')->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();

            $table->foreign('event_id')->references('id')->on('events')->cascadeOnDelete();
            $table->index(['event_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_guests');
    }
};
