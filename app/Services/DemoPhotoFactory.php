<?php

namespace App\Services;

use GdImage;

/**
 * Pembuat foto contoh.
 *
 * Dipakai dua tempat: mengisi galeri acara demo, dan menjadi gambar
 * pengganti saat berkas media hilang. Hasilnya bukan foto asli, tapi
 * sengaja disusun seperti jepretan candid di ruang acara — orang
 * dengan cahaya dari belakang, lampu bokeh, lalu butiran film — supaya
 * pada ukuran thumbnail tetap terbaca sebagai "foto orang", bukan
 * kotak abu-abu.
 *
 * Kuncinya ada tiga: proporsi tubuh dibuat mendekati manusia (tinggi
 * kepala kira-kira sepertujuh badan), latar diburamkan seperti lensa
 * bukaan lebar, dan tepi siluet dilembutkan — tanpa itu hasilnya
 * terlihat seperti boneka kayu.
 *
 * Semuanya digambar dengan GD, jadi repo tidak menyimpan berkas biner
 * dan gambarnya bisa dibuat ulang kapan saja.
 */
class DemoPhotoFactory
{
    /** Komposisi yang tersedia. */
    public const SCENES = ['couple', 'group', 'dance', 'portrait', 'toast'];

    /** Palet cahaya ruangan, dipilih bergantian agar album terasa variatif. */
    private const MOODS = [
        [[122, 82, 44], [16, 12, 9]],   // lilin hangat
        [[92, 62, 40], [12, 10, 9]],    // gedung remang
        [[146, 102, 56], [22, 16, 11]], // golden hour
        [[70, 62, 84], [10, 9, 12]],    // biru malam
        [[128, 90, 60], [18, 13, 10]],  // lampu taman
    ];

    /**
     * Gambar satu foto.
     *
     * @param  string|null  $scene  salah satu SCENES, atau null untuk otomatis
     */
    public function render(int $width, int $height, int $index = 0, ?string $scene = null): GdImage
    {
        $scene ??= self::SCENES[$index % count(self::SCENES)];
        $mood = self::MOODS[$index % count(self::MOODS)];

        // 1. Latar: gradasi cahaya ruangan + lampu bokeh, lalu diburamkan
        //    supaya terlihat seperti bidang fokus yang dangkal.
        $img = $this->renderBackdrop($width, $height, $mood);

        // 2. Orang-orangnya, digambar di lapisan sendiri agar tepinya
        //    bisa dilembutkan tanpa ikut memburamkan butiran film.
        $this->paintFigures($img, $width, $height, $scene, $mood);

        // 3. Sentuhan akhir.
        $this->paintHaze($img, $width, $height, $mood);
        $this->paintVignette($img, $width, $height);
        $this->paintGrain($img, $width, $height);

        return $img;
    }

    /** Simpan sebagai JPEG dan kembalikan ukuran berkasnya. */
    public function save(GdImage $image, string $absolutePath, int $quality = 86): int
    {
        if (! is_dir(dirname($absolutePath))) {
            mkdir(dirname($absolutePath), 0o755, true);
        }

        imagejpeg($image, $absolutePath, $quality);

        return (int) filesize($absolutePath);
    }

    // ------------------------------------------------------------------
    // Latar
    // ------------------------------------------------------------------

    private function renderBackdrop(int $w, int $h, array $mood): GdImage
    {
        // Digambar kecil lalu diperbesar: caranya murah dan hasil
        // pembesarannya justru memberi gradasi yang halus.
        $sw = 240;
        $sh = (int) round($sw * $h / $w);

        $small = imagecreatetruecolor($sw, $sh);
        imagealphablending($small, true);

        [$warm, $dark] = $mood;
        $lightX = $sw * $this->rand(0.28, 0.72);
        $lightY = $sh * $this->rand(0.14, 0.38);
        $reach = max($sw, $sh) * 1.05;

        for ($y = 0; $y < $sh; $y++) {
            for ($x = 0; $x < $sw; $x++) {
                $t = min(1, (sqrt(($x - $lightX) ** 2 + ($y - $lightY) ** 2) / $reach) ** 0.8);

                imagesetpixel($small, $x, $y, imagecolorallocate(
                    $small,
                    (int) round($warm[0] + ($dark[0] - $warm[0]) * $t),
                    (int) round($warm[1] + ($dark[1] - $warm[1]) * $t),
                    (int) round($warm[2] + ($dark[2] - $warm[2]) * $t)
                ));
            }
        }

        // Lampu-lampu tak fokus.
        $count = (int) $this->rand(16, 30);

        for ($i = 0; $i < $count; $i++) {
            $radius = (int) $this->rand($sw * 0.04, $sw * 0.16);
            $x = (int) $this->rand(0, $sw);
            $y = (int) $this->rand(0, $sh * 0.7);

            for ($ring = 6; $ring >= 1; $ring--) {
                $color = imagecolorallocatealpha(
                    $small,
                    (int) $this->rand(240, 255),
                    (int) $this->rand(212, 238),
                    (int) $this->rand(166, 206),
                    108 - $ring
                );

                $size = (int) ($radius * ($ring / 6));
                imagefilledellipse($small, $x, $y, $size, $size, $color);
            }
        }

        for ($i = 0; $i < 3; $i++) {
            imagefilter($small, IMG_FILTER_GAUSSIAN_BLUR);
        }

        $img = imagecreatetruecolor($w, $h);
        imagealphablending($img, true);
        imagecopyresampled($img, $small, 0, 0, 0, 0, $w, $h, $sw, $sh);
        imagedestroy($small);

        return $img;
    }

    // ------------------------------------------------------------------
    // Orang
    // ------------------------------------------------------------------

    /**
     * Susunan figur per komposisi: [posisi x relatif, skala, pose].
     *
     * @return array<int,array{0:float,1:float,2:string}>
     */
    private function layout(string $scene): array
    {
        return match ($scene) {
            'portrait' => [[0.5, 1.30, 'still']],
            'couple' => [[0.42, 1.02, 'still'], [0.58, 1.06, 'lean']],
            'toast' => [[0.28, 0.92, 'raise'], [0.5, 0.98, 'raise'], [0.72, 0.9, 'still']],
            'dance' => [[0.3, 0.95, 'raise'], [0.52, 1.0, 'lean'], [0.75, 0.93, 'raise']],
            default => [[0.16, 0.84, 'still'], [0.37, 0.92, 'lean'], [0.61, 0.88, 'still'], [0.84, 0.83, 'raise']],
        };
    }

    /** Warna kulit, rambut, dan pakaian. Diputar agar orangnya berbeda-beda. */
    private const SKINS = [
        [222, 176, 138], [198, 148, 110], [168, 120, 86], [236, 196, 162], [142, 98, 68],
    ];

    private const HAIRS = [
        [26, 20, 17], [44, 30, 22], [18, 15, 14], [62, 44, 30], [30, 24, 20],
    ];

    private const CLOTHES = [
        [38, 33, 44], [26, 30, 38], [52, 38, 34], [30, 28, 26], [44, 36, 30], [22, 24, 30],
    ];

    private function paintFigures(GdImage $img, int $w, int $h, string $scene, array $mood): void
    {
        $layer = imagecreatetruecolor($w, $h);
        imagealphablending($layer, false);
        imagesavealpha($layer, true);
        imagefill($layer, 0, 0, imagecolorallocatealpha($layer, 0, 0, 0, 127));
        imagealphablending($layer, true);

        // Badan berdiri di bawah bingkai supaya kaki terpotong,
        // seperti foto candid yang diambil sambil berdiri.
        $baseY = (int) ($h * 1.12);
        $figures = $this->layout($scene);

        // Yang paling jauh digambar lebih dulu supaya yang depan menimpanya.
        usort($figures, fn ($a, $b) => $a[1] <=> $b[1]);

        foreach ($figures as $index => [$position, $scale, $pose]) {
            $figureHeight = $h * 0.74 * $scale;

            $this->drawPerson(
                $layer,
                (int) ($w * $position),
                $baseY,
                $figureHeight,
                $pose,
                $index,
                $mood,
                // Figur kecil di belakang cukup jadi siluet — itu yang
                // memberi kesan kedalaman ruangan.
                $scale < 0.9
            );
        }

        imagefilter($layer, IMG_FILTER_GAUSSIAN_BLUR);

        imagecopy($img, $layer, 0, 0, 0, 0, $w, $h);
        imagedestroy($layer);
    }

    /**
     * Satu orang dengan wajah, rambut, dan pakaian.
     *
     * Cahaya utama datang dari belakang-samping (seperti lampu panggung
     * atau jendela), jadi sisi kanan tubuh mendapat garis terang dan
     * wajahnya sedikit lebih gelap dari tepinya.
     */
    private function drawPerson(
        GdImage $img,
        int $cx,
        int $baseY,
        float $height,
        string $pose,
        int $index,
        array $mood,
        bool $asSilhouette
    ): void {
        [$warm] = $mood;

        $skin = self::SKINS[$index % count(self::SKINS)];
        $hair = self::HAIRS[($index + 1) % count(self::HAIRS)];
        $cloth = self::CLOTHES[$index % count(self::CLOTHES)];

        // Ruangan remang: semua warna diredupkan dulu.
        $dim = fn (array $rgb, float $factor) => [
            (int) max(0, min(255, $rgb[0] * $factor)),
            (int) max(0, min(255, $rgb[1] * $factor)),
            (int) max(0, min(255, $rgb[2] * $factor)),
        ];

        $rim = imagecolorallocatealpha(
            $img,
            min(255, $warm[0] + 125),
            min(255, $warm[1] + 100),
            min(255, $warm[2] + 74),
            12
        );

        if ($asSilhouette) {
            $this->drawFigure($img, $cx - 3, $baseY - 3, $height * 1.012, $pose, $rim);
            $this->drawFigure($img, $cx, $baseY, $height, $pose, imagecolorallocatealpha($img, 12, 10, 9, 10));

            return;
        }

        // Garis cahaya di tepi tubuh.
        $this->drawFigure($img, $cx + 4, $baseY, $height * 1.02, $pose, $rim);

        // Tubuh berpakaian.
        $this->drawFigure(
            $img,
            $cx,
            $baseY,
            $height,
            $pose,
            imagecolorallocate($img, ...$dim($cloth, 0.85)),
            skipHead: true
        );

        $this->drawHead($img, $cx, $baseY, $height, $pose, $dim($skin, 0.62), $dim($hair, 0.7), $rim);
    }

    /** Kepala: rambut belakang, wajah, mata, mulut, lalu poni. */
    private function drawHead(
        GdImage $img,
        int $cx,
        int $baseY,
        float $height,
        string $pose,
        array $skin,
        array $hair,
        int $rim
    ): void {
        $headH = $height * 0.132;
        $headW = $headH * 0.76;
        $cy = (int) ($baseY - $height + $headH / 2);
        $lean = $pose === 'lean' ? (int) ($headW * 0.42) : 0;
        $x = $cx + $lean;

        $hairColor = imagecolorallocate($img, ...$hair);
        $skinColor = imagecolorallocate($img, ...$skin);
        $shadow = imagecolorallocatealpha($img, (int) ($skin[0] * 0.6), (int) ($skin[1] * 0.58), (int) ($skin[2] * 0.56), 40);
        $feature = imagecolorallocatealpha($img, (int) ($skin[0] * 0.3), (int) ($skin[1] * 0.28), (int) ($skin[2] * 0.3), 30);

        // Rambut belakang, sedikit lebih besar dari kepala.
        imagefilledellipse($img, $x, (int) ($cy - $headH * 0.06), (int) ($headW * 1.16), (int) ($headH * 1.12), $hairColor);

        // Wajah.
        imagefilledellipse($img, $x, $cy, (int) $headW, (int) $headH, $skinColor);

        // Sisi kiri wajah jatuh ke bayangan.
        imagefilledellipse(
            $img,
            (int) ($x - $headW * 0.22),
            $cy,
            (int) ($headW * 0.66),
            (int) ($headH * 0.92),
            $shadow
        );

        // Mata dan mulut — kecil saja, cukup untuk membaca sebagai wajah.
        $eyeY = (int) ($cy - $headH * 0.04);
        $eyeR = max(2, (int) ($headW * 0.1));
        imagefilledellipse($img, (int) ($x - $headW * 0.2), $eyeY, $eyeR, (int) max(2, $eyeR * 0.7), $feature);
        imagefilledellipse($img, (int) ($x + $headW * 0.2), $eyeY, $eyeR, (int) max(2, $eyeR * 0.7), $feature);
        imagefilledellipse(
            $img,
            $x,
            (int) ($cy + $headH * 0.24),
            (int) ($headW * 0.26),
            (int) max(2, $headH * 0.06),
            $feature
        );

        // Poni menutupi dahi.
        imagefilledellipse(
            $img,
            $x,
            (int) ($cy - $headH * 0.32),
            (int) ($headW * 1.04),
            (int) ($headH * 0.5),
            $hairColor
        );

        // Kilau tipis di tepi kanan rambut, searah sumber cahaya.
        imagefilledellipse($img, (int) ($x + $headW * 0.46), (int) ($cy - $headH * 0.1), (int) ($headW * 0.16), (int) ($headH * 0.6), $rim);
    }

    /**
     * Satu orang. Proporsinya memakai patokan gambar figur: tinggi
     * kepala kira-kira 1/7,5 tinggi badan, bahu sekitar dua kali lebar
     * kepala, pinggang lebih sempit dari bahu.
     */
    private function drawFigure(
        GdImage $img,
        int $cx,
        int $baseY,
        float $height,
        string $pose,
        int $color,
        bool $skipHead = false
    ): void {
        $headH = $height * 0.132;
        $headW = $headH * 0.76;
        $headCy = (int) ($baseY - $height + $headH / 2);

        $neckY = (int) ($headCy + $headH * 0.46);
        $shoulderY = (int) ($headCy + $headH * 0.78);
        $shoulderW = $headW * 2.5;
        $chestW = $headW * 2.2;
        $waistW = $headW * 1.75;
        $hipW = $headW * 2.05;
        $waistY = (int) ($shoulderY + $height * 0.26);
        $hipY = (int) ($shoulderY + $height * 0.40);

        $lean = $pose === 'lean' ? $headW * 0.42 : 0;

        // Kepala (sedikit lonjong) beserta massa rambut. Dilewati saat
        // wajahnya digambar terpisah dengan warna kulit dan rambut.
        if (! $skipHead) {
            imagefilledellipse($img, (int) ($cx + $lean), $headCy, (int) $headW, (int) $headH, $color);
            imagefilledellipse(
                $img,
                (int) ($cx + $lean),
                (int) ($headCy - $headH * 0.16),
                (int) ($headW * 1.1),
                (int) ($headH * 0.82),
                $color
            );
        }

        // Leher
        imagefilledrectangle(
            $img,
            (int) ($cx + $lean - $headW * 0.2),
            $neckY,
            (int) ($cx + $lean + $headW * 0.2),
            $shoulderY + 2,
            $color
        );

        // Badan: bahu miring, pinggang menyempit, pinggul melebar.
        imagefilledpolygon($img, [
            (int) ($cx + $lean - $shoulderW * 0.42), (int) ($shoulderY + $height * 0.012),
            (int) ($cx + $lean - $shoulderW * 0.5), (int) ($shoulderY + $height * 0.045),
            (int) ($cx - $chestW * 0.5), (int) ($shoulderY + $height * 0.13),
            (int) ($cx - $waistW * 0.5), $waistY,
            (int) ($cx - $hipW * 0.5), $hipY,
            (int) ($cx - $hipW * 0.46), $baseY,
            (int) ($cx + $hipW * 0.46), $baseY,
            (int) ($cx + $hipW * 0.5), $hipY,
            (int) ($cx + $waistW * 0.5), $waistY,
            (int) ($cx + $chestW * 0.5), (int) ($shoulderY + $height * 0.13),
            (int) ($cx + $lean + $shoulderW * 0.5), (int) ($shoulderY + $height * 0.045),
            (int) ($cx + $lean + $shoulderW * 0.42), (int) ($shoulderY + $height * 0.012),
        ], $color);

        // Bahu dibulatkan agar tidak bersudut tajam.
        imagefilledellipse(
            $img,
            (int) ($cx + $lean),
            (int) ($shoulderY + $height * 0.03),
            (int) $shoulderW,
            (int) ($height * 0.075),
            $color
        );

        $armW = max(3.0, $headW * 0.42);

        if ($pose === 'raise') {
            // Lengan kanan terangkat — orang bersulang atau menari.
            $this->limb(
                $img,
                $cx + $shoulderW * 0.44, $shoulderY + $height * 0.03,
                $cx + $shoulderW * 0.78, $headCy - $headH * 0.35,
                $armW, $color
            );
        } else {
            $this->limb(
                $img,
                $cx + $shoulderW * 0.42, $shoulderY + $height * 0.04,
                $cx + $shoulderW * 0.52, $waistY + $height * 0.06,
                $armW, $color
            );
        }

        $this->limb(
            $img,
            $cx - $shoulderW * 0.42, $shoulderY + $height * 0.04,
            $cx - $shoulderW * 0.5, $waistY + $height * 0.05,
            $armW, $color
        );
    }

    /** Anggota badan sebagai batang tebal berujung bulat. */
    private function limb(GdImage $img, float $x1, float $y1, float $x2, float $y2, float $width, int $color): void
    {
        $angle = atan2($y2 - $y1, $x2 - $x1) + M_PI / 2;
        $dx = cos($angle) * $width / 2;
        $dy = sin($angle) * $width / 2;

        imagefilledpolygon($img, [
            (int) ($x1 + $dx), (int) ($y1 + $dy),
            (int) ($x2 + $dx), (int) ($y2 + $dy),
            (int) ($x2 - $dx), (int) ($y2 - $dy),
            (int) ($x1 - $dx), (int) ($y1 - $dy),
        ], $color);

        imagefilledellipse($img, (int) $x1, (int) $y1, (int) $width, (int) $width, $color);
        imagefilledellipse($img, (int) $x2, (int) $y2, (int) $width, (int) $width, $color);
    }

    // ------------------------------------------------------------------
    // Sentuhan akhir
    // ------------------------------------------------------------------

    /** Kabut hangat tipis di bagian bawah supaya figur menyatu dengan latar. */
    private function paintHaze(GdImage $img, int $w, int $h, array $mood): void
    {
        [$warm] = $mood;

        for ($i = 0; $i < 3; $i++) {
            $haze = imagecolorallocatealpha($img, $warm[0], $warm[1], $warm[2], 124);
            $top = (int) ($h * (0.5 + $i * 0.14));
            imagefilledrectangle($img, 0, $top, $w, $h, $haze);
        }
    }

    /**
     * Sudut yang meredup.
     *
     * Dibuat sebagai topeng alpha kecil lalu diperbesar: menumpuk elips
     * beralpha rendah akan saling menambah dan menghitamkan seluruh
     * bingkai, sedangkan satu topeng hanya menggelapkan sesuai jaraknya
     * dari pusat.
     */
    private function paintVignette(GdImage $img, int $w, int $h): void
    {
        $sw = 120;
        $sh = 160;

        $mask = imagecreatetruecolor($sw, $sh);
        imagealphablending($mask, false);
        imagesavealpha($mask, true);

        for ($y = 0; $y < $sh; $y++) {
            for ($x = 0; $x < $sw; $x++) {
                $nx = ($x - $sw / 2) / ($sw / 2);
                $ny = ($y - $sh / 2) / ($sh / 2);
                $distance = min(1, sqrt($nx * $nx + $ny * $ny) / M_SQRT2);
                $strength = max(0, ($distance - 0.34) / 0.66);

                imagesetpixel($mask, $x, $y, imagecolorallocatealpha(
                    $mask,
                    0,
                    0,
                    0,
                    127 - (int) round($strength ** 1.5 * 92)
                ));
            }
        }

        imagealphablending($img, true);
        imagecopyresampled($img, $mask, 0, 0, 0, 0, $w, $h, $sw, $sh);
        imagedestroy($mask);
    }

    private function paintGrain(GdImage $img, int $w, int $h): void
    {
        $dots = (int) (($w * $h) / 120);

        for ($i = 0; $i < $dots; $i++) {
            $light = imagecolorallocatealpha($img, 255, 246, 232, (int) $this->rand(106, 124));
            imagesetpixel($img, (int) $this->rand(0, $w - 1), (int) $this->rand(0, $h - 1), $light);
        }

        for ($i = 0; $i < (int) ($dots * 0.6); $i++) {
            $dark = imagecolorallocatealpha($img, 8, 7, 6, (int) $this->rand(108, 124));
            imagesetpixel($img, (int) $this->rand(0, $w - 1), (int) $this->rand(0, $h - 1), $dark);
        }
    }

    private function rand(float $min, float $max): float
    {
        return $min + (mt_rand() / mt_getrandmax()) * ($max - $min);
    }
}
