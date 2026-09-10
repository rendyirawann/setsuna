<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Paket langganan. Satu paket dibeli untuk satu acara pernikahan
 * (one-off, bukan recurring) dan menentukan jatah tangkapan tiap tamu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('tagline')->nullable();
            $table->text('description')->nullable();

            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('compare_at_price', 12, 2)->nullable();
            $table->string('currency', 3)->default('IDR');

            // null = tanpa batas
            $table->unsignedInteger('max_guests')->nullable();

            // Jatah default per tamu — bisa ditimpa per event.
            $table->unsignedSmallInteger('photo_quota')->default(18);
            $table->unsignedSmallInteger('video_quota')->default(2);
            $table->unsignedSmallInteger('video_duration')->default(15); // detik
            $table->unsignedSmallInteger('boomerang_quota')->default(1);

            $table->unsignedSmallInteger('storage_days')->default(90);
            $table->json('features')->nullable();

            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
