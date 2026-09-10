<?php

namespace App\Support;

/**
 * Preset film untuk kamera tamu.
 *
 * "css" dipakai dua kali: sebagai filter live di preview kamera dan
 * sebagai ctx.filter saat frame digambar ke canvas, jadi hasil yang
 * tersimpan sama persis dengan yang dilihat tamu. Video memakai filter
 * yang sama pada canvas perekam.
 */
class FilmPresets
{
    /** @var array<string,array<string,mixed>> */
    public const PRESETS = [
        // Preset bawaan. Selalu ikut di setiap paket dan jadi pilihan
        // awal tiap acara: warna apa adanya, hanya dirapikan sedikit.
        'natural' => [
            'name' => 'Natural + Enhance',
            'description' => 'Warna apa adanya, hanya dirapikan sedikit: lebih jernih dan terang tanpa terlihat difilter. Preset bawaan yang selalu didapat setiap acara.',
            'css' => 'contrast(1.08) saturate(1.1) brightness(1.04)',
            'grain' => 0.0,
            'vignette' => 0.1,
            'default' => true,
        ],
        'classic' => [
            'name' => 'Classic 400',
            'description' => 'Warna hangat khas film 35mm.',
            'css' => 'contrast(1.08) saturate(1.05) sepia(0.14) brightness(1.02)',
            'grain' => 0.16,
            'vignette' => 0.35,
        ],
        'noir' => [
            'name' => 'Noir',
            'description' => 'Hitam putih kontras tinggi.',
            'css' => 'grayscale(1) contrast(1.22) brightness(1.03)',
            'grain' => 0.22,
            'vignette' => 0.45,
        ],
        'amber' => [
            'name' => 'Amber Hour',
            'description' => 'Nuansa emas untuk cahaya lilin.',
            'css' => 'sepia(0.3) saturate(1.25) contrast(1.05) brightness(1.05)',
            'grain' => 0.14,
            'vignette' => 0.3,
        ],
        'midnight' => [
            'name' => 'Midnight',
            'description' => 'Biru dingin, pas untuk resepsi malam.',
            'css' => 'saturate(0.9) contrast(1.15) brightness(1.06) hue-rotate(-8deg)',
            'grain' => 0.18,
            'vignette' => 0.4,
        ],
        'polaroid' => [
            'name' => 'Polaroid',
            'description' => 'Pudar lembut ala foto instan.',
            'css' => 'contrast(0.92) saturate(0.88) sepia(0.2) brightness(1.08)',
            'grain' => 0.12,
            'vignette' => 0.25,
        ],
        'flash' => [
            'name' => 'Direct Flash',
            'description' => 'Terang tajam seperti kamera sekali pakai.',
            'css' => 'contrast(1.3) saturate(1.15) brightness(1.12)',
            'grain' => 0.2,
            'vignette' => 0.5,
        ],
    ];

    /** Kunci preset bawaan yang selalu tersedia di semua paket. */
    public const DEFAULT = 'natural';

    /** @return array<string,array<string,mixed>> */
    public static function all(): array
    {
        return self::PRESETS;
    }

    /** Preset bawaan saja. */
    public static function default(): array
    {
        return self::PRESETS[self::DEFAULT];
    }

    /**
     * Preset film pilihan — semua kecuali yang bawaan.
     *
     * @return array<string,array<string,mixed>>
     */
    public static function selectable(): array
    {
        return array_filter(
            self::PRESETS,
            fn ($preset) => empty($preset['default'])
        );
    }

    /** @return array<string,string> Untuk dropdown di admin. */
    public static function options(): array
    {
        return array_map(fn ($preset) => $preset['name'], self::PRESETS);
    }

    public static function exists(?string $key): bool
    {
        return $key !== null && array_key_exists($key, self::PRESETS);
    }

    /** @return array<string,mixed> */
    public static function get(?string $key): array
    {
        return self::PRESETS[$key] ?? self::PRESETS['classic'];
    }

    public static function name(?string $key): string
    {
        return self::get($key)['name'];
    }

    public static function css(?string $key): string
    {
        return self::get($key)['css'];
    }
}
