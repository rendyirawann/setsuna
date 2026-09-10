<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventGuest;
use App\Models\EventMedia;
use App\Models\EventScan;
use App\Support\FilmPresets;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;
use RuntimeException;

/**
 * Semua tulis-menulis dari sisi kamera tamu: daftar tamu, simpan
 * tangkapan, dan potong kuota. Dipisah dari controller supaya aturan
 * kuota hanya hidup di satu tempat.
 */
class CaptureService
{
    /** Nama cookie berisi token tamu, satu per event. */
    public static function cookieName(Event $event): string
    {
        return 'setsuna_guest_' . substr(md5($event->id), 0, 12);
    }

    // ------------------------------------------------------------------
    // Tamu
    // ------------------------------------------------------------------

    public function findGuest(Event $event, ?string $token): ?EventGuest
    {
        if (! $token) {
            return null;
        }

        return $event->guests()->where('token', $token)->first();
    }

    /**
     * Daftarkan tamu baru. Kuota disalin dari event agar perubahan
     * setelan event tidak mengubah jatah tamu yang sudah mulai memotret.
     */
    public function registerGuest(Event $event, string $name, Request $request): EventGuest
    {
        if ($event->max_guests && $event->guests()->count() >= $event->max_guests) {
            throw new RuntimeException('Kuota tamu untuk acara ini sudah penuh.');
        }

        $agent = new Agent();
        $agent->setUserAgent((string) $request->userAgent());

        $guest = $event->guests()->create([
            'name' => Str::limit(trim($name), 40, ''),
            'token' => Str::random(48),
            'color' => EventGuest::COLORS[$event->guests()->count() % count(EventGuest::COLORS)],
            'photo_quota' => $event->photo_quota,
            'video_quota' => $event->video_quota,
            'boomerang_quota' => $event->boomerang_quota,
            'ip' => $request->ip(),
            'device' => $agent->device() ?: null,
            'user_agent' => Str::limit((string) $request->userAgent(), 480, ''),
            'last_active_at' => now(),
        ]);

        $this->logScan($event, $request, $guest);

        return $guest;
    }

    public function logScan(Event $event, Request $request, ?EventGuest $guest = null): void
    {
        $agent = new Agent();
        $agent->setUserAgent((string) $request->userAgent());

        EventScan::create([
            'event_id' => $event->id,
            'event_guest_id' => $guest?->id,
            'ip' => $request->ip(),
            'device' => $agent->device() ?: null,
            'platform' => $agent->platform() ?: null,
            'browser' => $agent->browser() ?: null,
            'user_agent' => Str::limit((string) $request->userAgent(), 480, ''),
        ]);

        $event->increment('scans_count');
    }

    // ------------------------------------------------------------------
    // Tangkapan
    // ------------------------------------------------------------------

    /**
     * Simpan satu tangkapan dan potong kuota tamu.
     *
     * Pemotongan kuota dilakukan di dalam transaksi dengan baris tamu
     * dikunci, supaya dua unggahan yang berbarengan dari satu ponsel
     * tidak bisa melewati jatah.
     */
    public function store(
        Event $event,
        EventGuest $guest,
        string $type,
        UploadedFile $file,
        ?UploadedFile $poster = null,
        array $meta = []
    ): EventMedia {
        if (! in_array($type, EventMedia::TYPES, true)) {
            throw new RuntimeException('Jenis tangkapan tidak dikenal.');
        }

        if ($guest->is_blocked) {
            throw new RuntimeException('Akses memotret untuk perangkat ini dinonaktifkan.');
        }

        if (! $event->isCaptureOpen()) {
            throw new RuntimeException($event->captureClosedReason());
        }

        $disk = Storage::disk('public');
        $folder = 'events/' . $event->slug . '/' . $type;
        $extension = $this->extensionFor($file, $type);
        $filename = Str::uuid()->toString() . '.' . $extension;

        $path = $file->storeAs($folder, $filename, 'public');

        if (! $path) {
            throw new RuntimeException('Gagal menyimpan berkas. Coba lagi.');
        }

        $thumbPath = null;
        $posterPath = null;
        $width = null;
        $height = null;

        if ($type === 'photo') {
            [$width, $height] = $this->dimensions($disk->path($path));
            $thumbPath = $this->makeThumbnail($disk->path($path), $folder, $filename);
        } elseif ($poster) {
            $posterName = pathinfo($filename, PATHINFO_FILENAME) . '.jpg';
            $posterPath = $poster->storeAs($folder . '/poster', $posterName, 'public');

            if ($posterPath) {
                [$width, $height] = $this->dimensions($disk->path($posterPath));
                $thumbPath = $this->makeThumbnail($disk->path($posterPath), $folder . '/poster', $posterName);
            }
        }

        try {
            return DB::transaction(function () use (
                $event, $guest, $type, $file, $path, $thumbPath, $posterPath, $width, $height, $meta
            ) {
                /** @var EventGuest $locked */
                $locked = EventGuest::whereKey($guest->id)->lockForUpdate()->firstOrFail();

                if (! $locked->canCapture($type)) {
                    throw new RuntimeException($this->exhaustedMessage($type));
                }

                $locked->increment(EventGuest::usageColumn($type));
                $locked->forceFill(['last_active_at' => now()])->save();

                $preset = FilmPresets::exists($meta['film_preset'] ?? null)
                    ? $meta['film_preset']
                    : $event->film_preset;

                return EventMedia::create([
                    'event_id' => $event->id,
                    'event_guest_id' => $locked->id,
                    'type' => $type,
                    'disk' => 'public',
                    'path' => $path,
                    'thumb_path' => $thumbPath,
                    'poster_path' => $posterPath,
                    'mime' => $file->getClientMimeType(),
                    'size' => $file->getSize() ?: 0,
                    'width' => $width,
                    'height' => $height,
                    'duration_ms' => isset($meta['duration_ms']) ? (int) $meta['duration_ms'] : null,
                    'film_preset' => $preset,
                    'status' => $event->moderation ? 'pending' : 'approved',
                    'captured_at' => now(),
                    'meta' => [
                        'facing' => $meta['facing'] ?? null,
                    ],
                ]);
            });
        } catch (RuntimeException|ModelNotFoundException $e) {
            // Kuota habis atau tamu hilang: jangan tinggalkan berkas yatim.
            foreach ([$path, $thumbPath, $posterPath] as $orphan) {
                if ($orphan && $disk->exists($orphan)) {
                    $disk->delete($orphan);
                }
            }

            throw $e instanceof RuntimeException
                ? $e
                : new RuntimeException('Sesi tamu tidak ditemukan. Muat ulang halaman.');
        }
    }

    public function exhaustedMessage(string $type): string
    {
        return match ($type) {
            'photo' => 'Jatah foto kamu sudah habis.',
            'video' => 'Jatah video kamu sudah habis.',
            'boomerang' => 'Jatah boomerang kamu sudah habis.',
            default => 'Jatah kamu sudah habis.',
        };
    }

    // ------------------------------------------------------------------
    // Berkas
    // ------------------------------------------------------------------

    private function extensionFor(UploadedFile $file, string $type): string
    {
        $guessed = strtolower((string) $file->guessExtension());

        if ($type === 'photo') {
            return in_array($guessed, ['jpg', 'jpeg', 'png', 'webp'], true) ? $guessed : 'jpg';
        }

        return in_array($guessed, ['mp4', 'webm', 'mov'], true) ? $guessed : 'webm';
    }

    /** @return array{0:?int,1:?int} */
    private function dimensions(string $absolutePath): array
    {
        $info = @getimagesize($absolutePath);

        return $info ? [(int) $info[0], (int) $info[1]] : [null, null];
    }

    /**
     * Thumbnail 720px sisi terpanjang. Grid galeri bisa berisi ratusan
     * gambar, jadi jangan pernah kirim berkas aslinya ke sana.
     */
    private function makeThumbnail(string $absolutePath, string $folder, string $filename): ?string
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        $info = @getimagesize($absolutePath);

        if (! $info) {
            return null;
        }

        [$width, $height] = $info;
        $source = match ($info['mime'] ?? '') {
            'image/jpeg' => @imagecreatefromjpeg($absolutePath),
            'image/png' => @imagecreatefrompng($absolutePath),
            'image/webp' => @imagecreatefromwebp($absolutePath),
            default => null,
        };

        if (! $source) {
            return null;
        }

        $max = 720;
        $scale = min(1, $max / max($width, $height));
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));

        $thumb = imagecreatetruecolor($targetWidth, $targetHeight);
        imagecopyresampled($thumb, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        $relative = $folder . '/thumb/' . pathinfo($filename, PATHINFO_FILENAME) . '.jpg';
        $destination = Storage::disk('public')->path($relative);

        if (! is_dir(dirname($destination))) {
            mkdir(dirname($destination), 0o755, true);
        }

        imagejpeg($thumb, $destination, 82);
        imagedestroy($thumb);
        imagedestroy($source);

        return $relative;
    }
}
