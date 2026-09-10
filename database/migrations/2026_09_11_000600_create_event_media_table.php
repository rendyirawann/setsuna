<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hasil jepretan tamu: foto, video pendek, dan boomerang.
 * Semuanya tersimpan per event sehingga galeri portal klien tinggal
 * memfilter berdasarkan event_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('event_id');
            $table->uuid('event_guest_id')->nullable();

            // photo | video | boomerang
            $table->string('type', 20)->default('photo');

            $table->string('disk', 30)->default('public');
            $table->string('path');
            $table->string('thumb_path')->nullable();
            $table->string('poster_path')->nullable();

            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();

            $table->string('film_preset', 40)->nullable();
            $table->string('caption')->nullable();

            // approved | pending | hidden | rejected
            $table->string('status', 20)->default('approved');
            $table->boolean('is_featured')->default(false);

            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('downloads_count')->default(0);

            $table->timestamp('captured_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('event_id')->references('id')->on('events')->cascadeOnDelete();
            $table->foreign('event_guest_id')->references('id')->on('event_guests')->nullOnDelete();

            $table->index(['event_id', 'status', 'created_at']);
            $table->index(['event_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_media');
    }
};
