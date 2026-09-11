<?php

namespace App\Support;

/**
 * URL aset dengan penanda versi.
 *
 * CSS dan JS di public/ tidak lewat build, jadi browser gemar menahannya
 * di cache dan perubahan tidak terlihat sampai pengguna melakukan hard
 * refresh. Menempelkan waktu ubah berkas membuat URL-nya berganti tiap
 * kali berkasnya benar-benar berubah.
 */
class Asset
{
    /** @var array<string,string> */
    private static array $cache = [];

    /**
     * Susun srcset dari varian yang dibuat `php artisan setsuna:images`.
     *
     * Varian dinamai "<nama>-<lebar>.webp" di folder yang sama dengan
     * berkas aslinya. Kalau belum pernah dibangun, hasilnya null dan
     * pemanggilnya cukup memakai berkas asli.
     */
    public static function srcset(string $path): ?string
    {
        $directory = dirname(public_path($path));
        $name = pathinfo($path, PATHINFO_FILENAME);
        $files = glob($directory . '/' . $name . '-*.webp') ?: [];

        $entries = [];

        foreach ($files as $file) {
            if (! preg_match('/-(\d+)\.webp$/', $file, $matches)) {
                continue;
            }

            $width = (int) $matches[1];
            $relative = dirname($path) . '/' . basename($file);

            $entries[$width] = asset($relative) . '?v=' . filemtime($file) . ' ' . $width . 'w';
        }

        if ($entries === []) {
            return null;
        }

        ksort($entries);

        return implode(', ', $entries);
    }

    public static function v(string $path): string
    {
        if (isset(self::$cache[$path])) {
            return self::$cache[$path];
        }

        $url = asset($path);
        $file = public_path($path);

        if (is_file($file)) {
            $url .= '?v=' . filemtime($file);
        }

        return self::$cache[$path] = $url;
    }
}
