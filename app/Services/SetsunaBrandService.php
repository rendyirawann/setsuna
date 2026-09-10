<?php

namespace App\Services;

use GdImage;

/**
 * Logo SETSUNA (刹那 · せつな — "sekejap").
 *
 * Lambangnya sebuah ensō, lingkaran sapuan kuas yang sengaja tidak
 * tertutup: dalam tradisi Zen ia digambar dalam satu tarikan napas dan
 * menerima ketidaksempurnaannya. Di sini ia sekaligus terbaca sebagai
 * cincin lensa. Celah di kanan atas diisi satu kilau — momen yang
 * tertangkap tepat sebelum hilang.
 *
 * Geometri yang sama dipakai untuk versi vektor dan raster, jadi logo
 * di tab browser, di halaman login, dan di navbar admin identik.
 *
 * Berkasnya ditulis ke direktori branding yang sudah dibaca
 * App\Support\Brand, sehingga tidak ada view yang perlu diubah.
 */
class SetsunaBrandService
{
    public const DIR = 'assets/media/branding';

    /** Palet: sumi (tinta) hangat dengan aksen sampanye. */
    private const INK_DARK = [12, 11, 10];
    private const INK_LIGHT = [26, 22, 19];
    private const GOLD_LIGHT = [244, 223, 192];
    private const GOLD_DARK = [199, 152, 94];
    private const CREAM = [247, 235, 217];

    /** Kanvas acuan 64x64. */
    private const CX = 32.0;
    private const CY = 33.5;
    private const RING_R = 19.0;

    /** Ensō dibuka di kanan atas; celah itu diisi kilau. */
    private const ARC_START = -48.0;
    private const ARC_SWEEP = 336.0;

    private const SPARK_X = 47.5;
    private const SPARK_Y = 16.5;
    private const SPARK_R = 8.6;

    /**
     * Bangun seluruh berkas logo.
     *
     * @return array<string,string> peta nama logis => path relatif public/
     */
    public function generate(string $name = 'SETSUNA', string $kana = 'せつな'): array
    {
        $dir = public_path(self::DIR);

        if (! is_dir($dir) && ! mkdir($dir, 0o755, true) && ! is_dir($dir)) {
            throw new \RuntimeException("Tidak bisa membuat direktori branding: {$dir}");
        }

        // --- Vektor ---------------------------------------------------
        file_put_contents("{$dir}/logo-mark.svg", $this->markSvg());
        file_put_contents("{$dir}/logo-full.svg", $this->lockupSvg($name, $kana, '#1A1512', '#8A7660'));
        file_put_contents("{$dir}/logo-full-dark.svg", $this->lockupSvg($name, $kana, '#F7EBD9', '#A8937A'));

        // --- Raster -----------------------------------------------------
        $master = $this->renderMark(1024);

        $sizes = [
            16 => 'favicon-16.png',
            32 => 'favicon-32.png',
            48 => 'favicon-48.png',
            64 => 'favicon-64.png',
            180 => 'apple-touch-icon.png',
            192 => 'icon-192.png',
            512 => 'icon-512.png',
        ];

        foreach ($sizes as $size => $file) {
            $this->savePng($master, $size, "{$dir}/{$file}");
        }

        file_put_contents("{$dir}/favicon.ico", $this->ico($master, [16, 32, 48]));

        $og = $this->renderOgImage($name, $kana);
        imagepng($og, "{$dir}/og-image.png", 9);
        imagedestroy($og);
        imagedestroy($master);

        file_put_contents("{$dir}/site.webmanifest", $this->manifest($name));

        return [
            'logo' => self::DIR . '/logo-mark.svg',
            'logo_full' => self::DIR . '/logo-full.svg',
            'logo_full_dark' => self::DIR . '/logo-full-dark.svg',
            'favicon' => self::DIR . '/favicon.ico',
            'favicon_png' => self::DIR . '/favicon-32.png',
            'apple_touch_icon' => self::DIR . '/apple-touch-icon.png',
            'og_image' => self::DIR . '/og-image.png',
            'manifest' => self::DIR . '/site.webmanifest',
            'theme_color' => '#0C0B0A',
        ];
    }

    // ------------------------------------------------------------------
    // Bentuk sapuan kuas
    // ------------------------------------------------------------------

    /**
     * Ketebalan kuas pada posisi t (0 di awal sapuan, 1 di ujung).
     *
     * Kuas ditekan di awal, penuh di tengah, lalu diangkat sehingga
     * ujungnya menipis — itulah yang membuat ensō terasa ditulis
     * tangan, bukan dicetak.
     */
    private function strokeWidth(float $t): float
    {
        $entry = min(1.0, $t / 0.12);          // tekanan awal
        $exit = min(1.0, (1 - $t) / 0.3);      // kuas diangkat
        $swell = 0.82 + 0.18 * sin($t * M_PI); // sedikit menggembung di tengah

        return 3.5 * $entry ** 0.5 * $exit ** 0.85 * $swell;
    }

    /**
     * Titik-titik sepanjang busur ensō.
     *
     * @return array<int,array{0:float,1:float,2:float}> [x, y, tebal]
     */
    private function strokePoints(int $steps = 220): array
    {
        $points = [];

        for ($i = 0; $i <= $steps; $i++) {
            $t = $i / $steps;
            $angle = deg2rad(self::ARC_START + self::ARC_SWEEP * $t);

            // Jari-jari sedikit bergoyang supaya lingkarannya tidak
            // sempurna — ensō memang tidak pernah bulat sempurna.
            $radius = self::RING_R + sin($t * M_PI * 2.4) * 0.42 + sin($t * M_PI * 5.1) * 0.18;

            $points[] = [
                self::CX + cos($angle) * $radius,
                self::CY + sin($angle) * $radius,
                $this->strokeWidth($t),
            ];
        }

        return $points;
    }

    /** @return array<int,array{0:float,1:float}> Titik bintang empat sudut. */
    private function sparkPoints(float $cx, float $cy, float $outer, float $inner): array
    {
        $points = [];

        for ($i = 0; $i < 8; $i++) {
            $angle = deg2rad($i * 45 - 90);
            $radius = $i % 2 === 0 ? $outer : $inner;
            $points[] = [$cx + cos($angle) * $radius, $cy + sin($angle) * $radius];
        }

        return $points;
    }

    // ------------------------------------------------------------------
    // SVG
    // ------------------------------------------------------------------

    /** Lambang saja, 64x64. */
    public function markSvg(): string
    {
        $path = $this->ensoPathData();

        $spark = implode(' ', array_map(
            fn ($point) => $this->n($point[0]) . ',' . $this->n($point[1]),
            $this->sparkPoints(self::SPARK_X, self::SPARK_Y, self::SPARK_R, self::SPARK_R * 0.24)
        ));

        return <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="64" height="64" role="img" aria-label="SETSUNA">
          <defs>
            <linearGradient id="sTile" x1="0" y1="0" x2="1" y2="1">
              <stop offset="0" stop-color="#1A1613"/>
              <stop offset="1" stop-color="#0A0908"/>
            </linearGradient>
            <linearGradient id="sGold" x1="0.1" y1="0" x2="0.9" y2="1">
              <stop offset="0" stop-color="#F4DFC0"/>
              <stop offset="0.5" stop-color="#DDB681"/>
              <stop offset="1" stop-color="#C7985E"/>
            </linearGradient>
          </defs>

          <rect width="64" height="64" rx="15" fill="url(#sTile)"/>
          <rect x="0.6" y="0.6" width="62.8" height="62.8" rx="14.4" fill="none"
                stroke="#F4DFC0" stroke-opacity="0.13" stroke-width="1.2"/>

          <!-- ensō: satu sapuan kuas yang tidak menutup -->
          <path d="{$path}" fill="url(#sGold)"/>

          <!-- kilau di celah sapuan -->
          <polygon points="{$spark}" fill="#F7EBD9"/>
        </svg>
        SVG;
    }

    /**
     * Ensō sebagai satu path tertutup: sisi luar ditelusuri maju, sisi
     * dalam ditelusuri mundur, sehingga tebalnya bisa berubah-ubah
     * seperti sapuan kuas sungguhan.
     */
    private function ensoPathData(): string
    {
        $points = $this->strokePoints();
        $outer = [];
        $inner = [];

        foreach ($points as $index => [$x, $y, $width]) {
            $next = $points[min($index + 1, count($points) - 1)];
            $previous = $points[max($index - 1, 0)];

            $angle = atan2($next[1] - $previous[1], $next[0] - $previous[0]) + M_PI / 2;
            $dx = cos($angle) * $width / 2;
            $dy = sin($angle) * $width / 2;

            $outer[] = [$x + $dx, $y + $dy];
            $inner[] = [$x - $dx, $y - $dy];
        }

        $path = 'M ' . $this->n($outer[0][0]) . ' ' . $this->n($outer[0][1]);

        foreach (array_slice($outer, 1) as [$x, $y]) {
            $path .= ' L ' . $this->n($x) . ' ' . $this->n($y);
        }

        foreach (array_reverse($inner) as [$x, $y]) {
            $path .= ' L ' . $this->n($x) . ' ' . $this->n($y);
        }

        return $path . ' Z';
    }

    /** Lambang + SETSUNA + kana. */
    public function lockupSvg(string $name, string $kana, string $textColor, string $subColor): string
    {
        $mark = preg_replace('/^\s*<svg[^>]*>/', '', trim($this->markSvg()));
        $mark = preg_replace('/<\/svg>\s*$/', '', (string) $mark);
        $mark = str_replace(
            ['id="sTile"', 'url(#sTile)', 'id="sGold"', 'url(#sGold)'],
            ['id="lTile"', 'url(#lTile)', 'id="lGold"', 'url(#lGold)'],
            (string) $mark
        );

        $label = htmlspecialchars($name, ENT_QUOTES);
        $sub = htmlspecialchars($kana, ENT_QUOTES);

        return <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 64" width="300" height="64" role="img" aria-label="{$label}">
          {$mark}
          <text x="82" y="34" font-family="'Inter', 'Segoe UI', system-ui, sans-serif"
                font-size="25" font-weight="500" letter-spacing="7" fill="{$textColor}">{$label}</text>
          <text x="84" y="50" font-family="'Shippori Mincho', 'Yu Mincho', 'Hiragino Mincho ProN', serif"
                font-size="12" letter-spacing="6" fill="{$subColor}">{$sub}</text>
        </svg>
        SVG;
    }

    private function n(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    // ------------------------------------------------------------------
    // Raster
    // ------------------------------------------------------------------

    /** Gambar lambang pada kanvas persegi berukuran $size. */
    public function renderMark(int $size): GdImage
    {
        $img = imagecreatetruecolor($size, $size);
        imagealphablending($img, true);
        imagesavealpha($img, true);
        imagefill($img, 0, 0, imagecolorallocatealpha($img, 0, 0, 0, 127));

        $s = $size / 64;

        $this->roundedGradient($img, $size, 15 * $s);

        // Sapuan digambar sebagai deretan cakram yang jari-jarinya
        // berubah — cara paling sederhana untuk meniru kuas.
        foreach ($this->strokePoints(420) as $index => [$x, $y, $width]) {
            $t = $index / 420;

            // Warna sedikit menggelap di ujung, seperti tinta menipis.
            $mix = 0.25 + 0.75 * sin(max(0.0, min(1.0, $t)) * M_PI);
            $color = imagecolorallocate(
                $img,
                (int) round(self::GOLD_DARK[0] + (self::GOLD_LIGHT[0] - self::GOLD_DARK[0]) * $mix),
                (int) round(self::GOLD_DARK[1] + (self::GOLD_LIGHT[1] - self::GOLD_DARK[1]) * $mix),
                (int) round(self::GOLD_DARK[2] + (self::GOLD_LIGHT[2] - self::GOLD_DARK[2]) * $mix)
            );

            $diameter = max(1, (int) round($width * $s));
            imagefilledellipse($img, (int) round($x * $s), (int) round($y * $s), $diameter, $diameter, $color);
        }

        // Kilau
        $spark = [];

        foreach ($this->sparkPoints(self::SPARK_X, self::SPARK_Y, self::SPARK_R, self::SPARK_R * 0.24) as [$x, $y]) {
            $spark[] = (int) round($x * $s);
            $spark[] = (int) round($y * $s);
        }

        imagefilledpolygon($img, $spark, imagecolorallocate($img, ...self::CREAM));

        return $img;
    }

    /** Ubin sudut membulat dengan gradasi gelap. */
    private function roundedGradient(GdImage $img, int $size, float $radius): void
    {
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                if (! $this->insideRounded($x, $y, $size, $radius)) {
                    continue;
                }

                $t = ($x + $y) / (2 * $size);

                imagesetpixel($img, $x, $y, imagecolorallocate(
                    $img,
                    (int) round(self::INK_LIGHT[0] + (self::INK_DARK[0] - self::INK_LIGHT[0]) * $t),
                    (int) round(self::INK_LIGHT[1] + (self::INK_DARK[1] - self::INK_LIGHT[1]) * $t),
                    (int) round(self::INK_LIGHT[2] + (self::INK_DARK[2] - self::INK_LIGHT[2]) * $t)
                ));
            }
        }
    }

    private function insideRounded(int $x, int $y, int $size, float $radius): bool
    {
        $corners = [
            [$radius, $radius],
            [$size - $radius, $radius],
            [$radius, $size - $radius],
            [$size - $radius, $size - $radius],
        ];

        foreach ($corners as $index => [$cx, $cy]) {
            $outsideX = $index % 2 === 0 ? $x < $cx : $x > $cx;
            $outsideY = $index < 2 ? $y < $cy : $y > $cy;

            if ($outsideX && $outsideY) {
                return (($x - $cx) ** 2 + ($y - $cy) ** 2) <= $radius ** 2;
            }
        }

        return true;
    }

    private function savePng(GdImage $master, int $size, string $path): void
    {
        $out = imagecreatetruecolor($size, $size);
        imagealphablending($out, false);
        imagesavealpha($out, true);
        imagefill($out, 0, 0, imagecolorallocatealpha($out, 0, 0, 0, 127));
        imagecopyresampled($out, $master, 0, 0, 0, 0, $size, $size, imagesx($master), imagesy($master));
        imagepng($out, $path, 9);
        imagedestroy($out);
    }

    /** Wadah .ico berisi beberapa PNG. */
    private function ico(GdImage $master, array $sizes): string
    {
        $images = [];

        foreach ($sizes as $size) {
            $tmp = tempnam(sys_get_temp_dir(), 'ico');
            $this->savePng($master, $size, $tmp);
            $images[$size] = (string) file_get_contents($tmp);
            @unlink($tmp);
        }

        $header = pack('vvv', 0, 1, count($images));
        $offset = 6 + 16 * count($images);
        $entries = '';
        $body = '';

        foreach ($images as $size => $png) {
            $entries .= pack('CCCCvvVV', $size, $size, 0, 0, 1, 32, strlen($png), $offset);
            $offset += strlen($png);
            $body .= $png;
        }

        return $header . $entries . $body;
    }

    /** Kartu Open Graph 1200x630. */
    private function renderOgImage(string $name, string $kana): GdImage
    {
        $w = 1200;
        $h = 630;
        $img = imagecreatetruecolor($w, $h);
        imagealphablending($img, true);
        imagesavealpha($img, true);

        for ($y = 0; $y < $h; $y++) {
            $t = $y / $h;
            imagefilledrectangle($img, 0, $y, $w, $y, imagecolorallocate(
                $img,
                (int) round(22 + (10 - 22) * $t),
                (int) round(19 + (9 - 19) * $t),
                (int) round(16 + (8 - 16) * $t)
            ));
        }

        $mark = $this->renderMark(230);
        imagecopy($img, $mark, 92, 138, 0, 0, 230, 230);
        imagedestroy($mark);

        $gold = imagecolorallocate($img, ...self::GOLD_LIGHT);
        $cream = imagecolorallocate($img, ...self::CREAM);
        $muted = imagecolorallocate($img, 152, 136, 118);
        $font = $this->font();

        if ($font) {
            imagettftext($img, 58, 0, 96, 440, $cream, $font, $name);
            imagettftext($img, 22, 0, 100, 486, $gold, $font, $kana . '  ·  sekejap yang tersimpan');
            imagettftext($img, 18, 0, 100, 542, $muted, $font, 'Kamera tamu berbasis QR untuk acara yang cuma sekali');
        }

        return $img;
    }

    private function font(): ?string
    {
        $candidates = [
            'C:/Windows/Fonts/seguibl.ttf',
            'C:/Windows/Fonts/segoeuib.ttf',
            'C:/Windows/Fonts/arialbd.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/System/Library/Fonts/Supplemental/Arial Bold.ttf',
        ];

        foreach ($candidates as $font) {
            if (is_file($font)) {
                return $font;
            }
        }

        return null;
    }

    private function manifest(string $name): string
    {
        return json_encode([
            'name' => $name,
            'short_name' => $name,
            'icons' => [
                ['src' => '/' . self::DIR . '/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => '/' . self::DIR . '/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png'],
            ],
            'theme_color' => '#0C0B0A',
            'background_color' => '#0C0B0A',
            'display' => 'standalone',
            'start_url' => '/',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
