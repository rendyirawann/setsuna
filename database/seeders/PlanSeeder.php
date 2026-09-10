<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * Paket yang tampil di halaman harga.
 */
class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Intimate',
                'slug' => 'intimate',
                'tagline' => 'Untuk akad dan resepsi kecil yang hangat.',
                'description' => 'Cocok untuk acara di rumah atau restoran dengan tamu terbatas.',
                'price' => 2500000,
                'compare_at_price' => 3000000,
                'max_guests' => 100,
                'photo_quota' => 12,
                'video_quota' => 1,
                'video_duration' => 15,
                'boomerang_quota' => 1,
                'storage_days' => 60,
                'features' => [
                    'Sampai 100 tamu',
                    '12 foto tiap tamu',
                    '1 video 15 detik',
                    '1 boomerang',
                    'Preset Natural + Enhance (bawaan)',
                    'Album online 60 hari',
                    'Unduh semua hasil dalam satu zip',
                ],
                'sort_order' => 1,
            ],
            [
                'name' => 'Signature',
                'slug' => 'signature',
                'tagline' => 'Paket favorit untuk resepsi gedung.',
                'description' => 'Jatah paling pas: tamu puas memotret, album tetap terkurasi.',
                'price' => 4500000,
                'compare_at_price' => 5500000,
                'max_guests' => 400,
                'photo_quota' => 18,
                'video_quota' => 2,
                'video_duration' => 15,
                'boomerang_quota' => 1,
                'storage_days' => 90,
                'features' => [
                    'Sampai 400 tamu',
                    '18 foto tiap tamu',
                    '2 video 15 detik',
                    '1 boomerang',
                    'Preset bawaan + 6 preset film pilihan',
                    'Album online 90 hari',
                    'Papan QR siap cetak',
                    'Moderasi foto sebelum tayang',
                ],
                'is_featured' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Grand',
                'slug' => 'grand',
                'tagline' => 'Tanpa batas tamu, untuk pesta besar.',
                'description' => 'Kapasitas penuh dan penyimpanan setahun untuk acara berskala besar.',
                'price' => 7500000,
                'compare_at_price' => 9000000,
                'max_guests' => null,
                'photo_quota' => 24,
                'video_quota' => 3,
                'video_duration' => 15,
                'boomerang_quota' => 2,
                'storage_days' => 365,
                'features' => [
                    'Tamu tanpa batas',
                    '24 foto tiap tamu',
                    '3 video 15 detik',
                    '2 boomerang',
                    'Preset bawaan + 6 preset film pilihan',
                    'Album online 1 tahun',
                    'Papan QR siap cetak',
                    'Prioritas dukungan hari-H',
                    'Domain portal khusus',
                ],
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }

        $this->command->info('Paket langganan siap: ' . count($plans) . ' paket.');
    }
}
