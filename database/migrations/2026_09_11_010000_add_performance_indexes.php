<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index untuk jalur baca yang sering dipakai.
 *
 * PostgreSQL tidak membuat index otomatis untuk foreign key — hanya untuk
 * primary key dan unique. Akibatnya setiap "media milik tamu ini",
 * "acara milik klien ini", dan setiap cascade delete berujung sequential
 * scan. Di acara besar tabel event_media bisa berisi puluhan ribu baris,
 * jadi ini terasa.
 *
 * Kolom urutan ikut dimasukkan ke index supaya "ambil terbaru" tidak
 * perlu menyortir hasilnya lagi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_media', function (Blueprint $table) {
            // Roll pribadi tamu dan album per tamu: filter guest + urut waktu.
            $table->index(['event_guest_id', 'created_at'], 'event_media_guest_created_index');

            // Galeri mengurutkan dengan captured_at, bukan created_at.
            $table->index(['event_id', 'status', 'captured_at'], 'event_media_event_status_captured_index');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->index('client_id', 'events_client_id_index');
            $table->index('plan_id', 'events_plan_id_index');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->index('client_id', 'subscriptions_client_id_index');
            $table->index('event_id', 'subscriptions_event_id_index');
            $table->index('plan_id', 'subscriptions_plan_id_index');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->index('user_id', 'clients_user_id_index');
        });

        Schema::table('event_scans', function (Blueprint $table) {
            $table->index('event_guest_id', 'event_scans_guest_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('event_media', function (Blueprint $table) {
            $table->dropIndex('event_media_guest_created_index');
            $table->dropIndex('event_media_event_status_captured_index');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex('events_client_id_index');
            $table->dropIndex('events_plan_id_index');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex('subscriptions_client_id_index');
            $table->dropIndex('subscriptions_event_id_index');
            $table->dropIndex('subscriptions_plan_id_index');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex('clients_user_id_index');
        });

        Schema::table('event_scans', function (Blueprint $table) {
            $table->dropIndex('event_scans_guest_id_index');
        });
    }
};
