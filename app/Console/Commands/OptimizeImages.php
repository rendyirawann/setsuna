<?php

namespace App\Console\Commands;

use App\Models\EventMedia;
use App\Services\ImageOptimizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class OptimizeImages extends Command
{
    protected $signature = 'setsuna:images
                            {--hero : Bangun varian responsif untuk gambar hero}
                            {--media : Bangun ulang thumbnail media tamu (WebP + varian 360px)}
                            {--force : Timpa varian yang sudah ada}';

    protected $description = 'Kecilkan dan kompres gambar: varian responsif hero dan thumbnail media tamu';

    /** Lebar varian hero. Dipasangkan dengan srcset di beranda. */
    private const HERO_WIDTHS = [640, 1024, 1440, 1920];

    public function handle(): int
    {
        $all = ! $this->option('hero') && ! $this->option('media');

        if ($all || $this->option('hero')) {
            $this->buildHero();
        }

        if ($all || $this->option('media')) {
            $this->rebuildMediaThumbnails();
        }

        return self::SUCCESS;
    }

    /**
     * Hero adalah gambar terbesar di beranda dan yang pertama dilihat,
     * jadi ia dibuat dalam beberapa lebar supaya ponsel tidak perlu
     * mengunduh versi desktop.
     */
    private function buildHero(): void
    {
        $source = public_path('assets/media/hero/hero.jpg');

        if (! is_file($source)) {
            $this->warn('Gambar hero tidak ada: ' . $source);

            return;
        }

        $this->info('Membangun varian hero ...');

        $image = ImageOptimizer::read($source);

        if (! $image) {
            $this->error('Gambar hero tidak terbaca.');

            return;
        }

        $extension = ImageOptimizer::bestExtension();
        $original = (int) filesize($source);
        $total = 0;

        // Gambar tidak pernah diperbesar, jadi setiap lebar di atas ukuran
        // sumber akan menghasilkan berkas yang sama persis. Ambil hanya
        // lebar yang lebih kecil, lalu tambahkan satu varian ukuran penuh.
        $sourceWidth = imagesx($image);
        $widths = array_values(array_filter(self::HERO_WIDTHS, fn ($w) => $w < $sourceWidth));
        $widths[] = $sourceWidth;

        foreach ($widths as $width) {
            $destination = public_path("assets/media/hero/hero-{$width}.{$extension}");

            if (is_file($destination) && ! $this->option('force')) {
                $this->line(sprintf('  <fg=gray>lewati</> hero-%d.%s (sudah ada)', $width, $extension));
                $total += (int) filesize($destination);

                continue;
            }

            $result = ImageOptimizer::resizeTo($image, $destination, $width);

            if (! $result) {
                $this->warn("  gagal membuat lebar {$width}");

                continue;
            }

            $total += $result['bytes'];
            $this->line(sprintf(
                '  hero-%d.%s  %dx%d  %s KB',
                $width,
                $extension,
                $result['width'],
                $result['height'],
                number_format($result['bytes'] / 1024, 0)
            ));
        }

        imagedestroy($image);

        $this->line(sprintf(
            '  <fg=gray>asli %s KB, semua varian %s KB</>',
            number_format($original / 1024, 0),
            number_format($total / 1024, 0)
        ));
    }

    /**
     * Bangun ulang thumbnail media yang sudah tersimpan.
     *
     * Berguna untuk media yang diunggah sebelum thumbnail WebP dan
     * varian 360px ada. Berkas aslinya tidak disentuh sama sekali.
     */
    private function rebuildMediaThumbnails(): void
    {
        $query = EventMedia::query()->whereNotNull('thumb_path');

        $count = $query->count();

        if ($count === 0) {
            $this->info('Tidak ada media untuk diproses.');

            return;
        }

        $this->info("Membangun ulang thumbnail untuk {$count} media ...");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $rebuilt = 0;
        $savedBytes = 0;

        $query->chunkById(100, function ($items) use (&$rebuilt, &$savedBytes, $bar) {
            foreach ($items as $media) {
                $bar->advance();

                $disk = Storage::disk($media->disk);

                // Sumbernya foto asli, atau poster untuk video.
                $sourcePath = $media->type === 'photo' ? $media->path : $media->poster_path;

                if (! $sourcePath || ! $disk->exists($sourcePath)) {
                    continue;
                }

                $hasSmall = ! empty($media->meta['thumb_sm'] ?? null);
                $isWebp = str_ends_with(strtolower((string) $media->thumb_path), '.webp');

                if ($hasSmall && $isWebp && ! $this->option('force')) {
                    continue;
                }

                $before = $disk->exists($media->thumb_path) ? $disk->size($media->thumb_path) : 0;

                $source = ImageOptimizer::read($disk->path($sourcePath));

                if (! $source) {
                    continue;
                }

                $folder = dirname($sourcePath);
                $extension = ImageOptimizer::bestExtension();
                $name = pathinfo($sourcePath, PATHINFO_FILENAME) . '.' . $extension;

                $large = $folder . '/thumb/' . $name;
                $small = $folder . '/thumb/sm/' . $name;

                $largeResult = ImageOptimizer::resizeTo($source, $disk->path($large), 720);
                $smallResult = ImageOptimizer::resizeTo($source, $disk->path($small), 360);

                imagedestroy($source);

                if (! $largeResult) {
                    continue;
                }

                // Thumbnail lama dibuang hanya kalau penggantinya beda berkas.
                $old = $media->thumb_path;

                if ($old && $old !== $large && $disk->exists($old)) {
                    $disk->delete($old);
                }

                $media->forceFill([
                    'thumb_path' => $large,
                    'meta' => array_merge($media->meta ?? [], [
                        'thumb_sm' => $smallResult ? $small : null,
                    ]),
                ])->save();

                $rebuilt++;
                $savedBytes += max(0, $before - $largeResult['bytes']);
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->info(sprintf(
            'Selesai: %d thumbnail dibangun ulang, hemat sekitar %s KB pada ukuran besar.',
            $rebuilt,
            number_format($savedBytes / 1024, 0)
        ));
    }
}
