<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Acara: pernikahan, wisuda, prom, gathering kantor, ulang tahun.
 * Kolom "slug" adalah route portal publik milik klien
 * (mis. /pernikahan-emma) — di sinilah galeri tamu tersimpan dan tampil.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();

            $table->string('slug')->unique();          // route portal
            $table->string('title');

            // wedding | graduation | prom | corporate | birthday | other
            $table->string('event_type', 30)->default('wedding');

            // Dipakai acara berpasangan (pernikahan); acara lain
            // memakai host_name, mis. nama kampus atau perusahaan.
            $table->string('bride_name')->nullable();
            $table->string('groom_name')->nullable();
            $table->string('host_name')->nullable();
            $table->string('hashtag')->nullable();

            $table->date('event_date')->nullable();
            $table->string('venue')->nullable();
            $table->string('city')->nullable();
            $table->text('address')->nullable();

            $table->string('cover_path')->nullable();
            $table->text('welcome_message')->nullable();
            $table->text('sign_message')->nullable();   // teks di papan QR

            $table->string('theme', 40)->default('noir');
            $table->string('film_preset', 40)->default('classic');

            // Jatah per tamu (disalin dari plan saat dibuat, boleh ditimpa).
            $table->unsignedSmallInteger('photo_quota')->default(18);
            $table->unsignedSmallInteger('video_quota')->default(2);
            $table->unsignedSmallInteger('video_duration')->default(15);
            $table->unsignedSmallInteger('boomerang_quota')->default(1);
            $table->unsignedInteger('max_guests')->nullable();

            // instant | after_event | manual
            $table->string('gallery_reveal', 20)->default('after_event');
            $table->boolean('gallery_public')->default(true);
            $table->string('gallery_password')->nullable();
            $table->boolean('gallery_unlocked')->default(false); // dipakai mode manual

            $table->boolean('require_guest_name')->default(true);
            $table->boolean('allow_download')->default(true);
            $table->boolean('moderation')->default(false);

            $table->timestamp('capture_opens_at')->nullable();
            $table->timestamp('capture_closes_at')->nullable();
            $table->timestamp('media_expires_at')->nullable();

            // draft | active | paused | completed | archived
            $table->string('status', 20)->default('draft');

            $table->string('qr_token', 40)->unique();
            $table->unsignedInteger('scans_count')->default(0);
            $table->unsignedInteger('views_count')->default(0);

            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'event_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
