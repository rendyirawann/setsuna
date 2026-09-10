<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Identitas merek dan meta SEO.
 *
 * Nilai bawaan tabel settings dibuat saat migrasi dan masih memakai nama
 * kerangka lama, jadi setiap `migrate:fresh` akan mengembalikannya.
 * Seeder ini yang menegaskan identitas SETSUNA supaya tidak hilang lagi.
 */
class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'site_name' => 'SETSUNA',
            'site_short_name' => 'SETSUNA',
            'site_tagline' => '刹那 · せつな — sekejap yang tersimpan',

            'site_description' => 'SETSUNA (刹那 · せつな) mengubah satu QR jadi kamera sekali pakai '
                . 'di ponsel setiap tamu. Untuk pernikahan, wisuda, prom, sampai gathering kantor — '
                . 'setiap tamu dapat jatah terbatas, dan semua jepretannya berkumpul jadi satu album '
                . 'yang terbuka bersamaan.',

            'site_keywords' => 'setsuna, せつな, 刹那, kamera tamu, qr kamera undangan, '
                . 'disposable camera digital, album pernikahan online, dokumentasi wisuda, '
                . 'foto prom sekolah, galeri acara, kamera tanpa aplikasi, photobooth qr, '
                . 'guest camera, wedding qr gallery',

            'site_author' => 'Rendy Irawan',
            'seo_robots' => 'index, follow',
            'site_theme_color' => '#0C0B0A',

            'site_logo' => 'assets/media/branding/logo-mark.svg',
            'site_favicon' => 'assets/media/branding/favicon.ico',
            'site_og_image' => 'assets/media/branding/og-image.png',

            // Kredit pembuat di footer. Kosongkan salah satunya kalau
            // tautannya tidak ingin ditampilkan.
            'owner_name' => 'Rendy Irawan',
            'owner_github' => 'https://github.com/rendyirawann',
            'owner_linkedin' => 'https://linkedin.com/in/rendyirawann',
        ];

        foreach ($settings as $key => $value) {
            Setting::set($key, $value);
        }

        Setting::clearCache();

        $this->command->info('Identitas SETSUNA disimpan.');
    }
}
