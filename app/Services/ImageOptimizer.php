<?php

namespace App\Services;

use GdImage;

/**
 * Pengecil dan pengompres gambar.
 *
 * Dipakai dua tempat: membuat thumbnail saat tamu mengunggah, dan
 * membangun varian aset statis lewat perintah artisan.
 *
 * WebP dipilih bila GD mendukungnya. Pada kualitas 82 selisihnya tidak
 * terlihat mata, tapi berkasnya kira-kira separuh JPEG dengan kualitas
 * setara — itu yang menentukan saat ratusan tamu membuka galeri lewat
 * WiFi venue yang sesak.
 */
class ImageOptimizer
{
    /** Kualitas yang dipakai; cukup tinggi untuk tidak terlihat bedanya. */
    public const WEBP_QUALITY = 82;
    public const JPEG_QUALITY = 84;

    public static function supportsWebp(): bool
    {
        return function_exists('imagewebp') && (bool) (gd_info()['WebP Support'] ?? false);
    }

    /** Ekstensi terbaik yang tersedia untuk keluaran. */
    public static function bestExtension(): string
    {
        return self::supportsWebp() ? 'webp' : 'jpg';
    }

    /** Baca berkas gambar apa pun yang didukung GD. */
    public static function read(string $path): ?GdImage
    {
        $info = @getimagesize($path);

        if (! $info) {
            return null;
        }

        $image = match ($info['mime'] ?? '') {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            'image/gif' => @imagecreatefromgif($path),
            default => null,
        };

        return $image ?: null;
    }

    /**
     * Tulis salinan yang diperkecil.
     *
     * Gambar tidak pernah diperbesar: kalau sumbernya sudah lebih kecil
     * dari target, ukurannya dipertahankan supaya tidak jadi buram.
     *
     * @return array{path:string,width:int,height:int,bytes:int}|null
     */
    public static function resizeTo(GdImage $source, string $destination, int $maxEdge): ?array
    {
        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, $maxEdge / max($width, $height));

        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);

        // Pertahankan transparansi kalau sumbernya punya.
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
        imagealphablending($canvas, true);

        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        if (! is_dir(dirname($destination)) && ! mkdir(dirname($destination), 0o755, true) && ! is_dir(dirname($destination))) {
            imagedestroy($canvas);

            return null;
        }

        $written = str_ends_with(strtolower($destination), '.webp')
            ? imagewebp($canvas, $destination, self::WEBP_QUALITY)
            : imagejpeg($canvas, $destination, self::JPEG_QUALITY);

        imagedestroy($canvas);

        if (! $written || ! is_file($destination)) {
            return null;
        }

        return [
            'path' => $destination,
            'width' => $targetWidth,
            'height' => $targetHeight,
            'bytes' => (int) filesize($destination),
        ];
    }

    /** Ganti ekstensi sebuah path. */
    public static function withExtension(string $path, string $extension): string
    {
        $directory = trim(dirname($path), './\\');
        $name = pathinfo($path, PATHINFO_FILENAME);

        return ($directory !== '' ? $directory . '/' : '') . $name . '.' . $extension;
    }
}
