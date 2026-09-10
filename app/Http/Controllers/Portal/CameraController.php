<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventGuest;
use App\Services\CaptureService;
use App\Support\FilmPresets;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\View\View;
use RuntimeException;

/**
 * Kamera sekali pakai untuk tamu undangan.
 *
 * Halaman ini yang dibuka QR: begitu dipindai, tamu langsung berada di
 * mode kamera, tanpa login dan tanpa memasang aplikasi.
 */
class CameraController extends Controller
{
    public function __construct(private readonly CaptureService $captures)
    {
    }

    /** Tampilan kamera. Dipanggil langsung oleh QR di venue. */
    public function show(Request $request, Event $event): View
    {
        $guest = $this->guest($request, $event);

        // Scan pertama dari perangkat ini: catat untuk statistik tuan rumah.
        if (! $guest) {
            $this->captures->logScan($event, $request);
        }

        if (! $event->isCaptureOpen()) {
            return view('portal.closed', [
                'event' => $event,
                'reason' => $event->captureClosedReason(),
                'guest' => $guest,
            ]);
        }

        return view('portal.camera', [
            'event' => $event,
            'guest' => $guest,
            'preset' => FilmPresets::get($event->film_preset),
            'presets' => FilmPresets::all(),
            'config' => [
                'endpoints' => [
                    'register' => route('portal.guest.register', $event->slug),
                    'capture' => route('portal.capture', $event->slug),
                    'quota' => route('portal.quota', $event->slug),
                    'roll' => route('portal.roll.api', $event->slug),
                    'gallery' => $event->galleryUrl(),
                    'portal' => $event->portalUrl(),
                ],
                'quota' => $guest
                    ? $guest->quotaSummary()
                    : [
                        'photo' => $event->photo_quota,
                        'video' => $event->video_quota,
                        'boomerang' => $event->boomerang_quota,
                        'photo_total' => $event->photo_quota,
                        'video_total' => $event->video_quota,
                        'boomerang_total' => $event->boomerang_quota,
                    ],
                'videoDuration' => $event->video_duration,
                'preset' => [
                    'key' => $event->film_preset,
                    'css' => FilmPresets::css($event->film_preset),
                    'grain' => FilmPresets::get($event->film_preset)['grain'],
                    'vignette' => FilmPresets::get($event->film_preset)['vignette'],
                ],
                'guest' => $guest ? ['name' => $guest->name] : null,
                'requireName' => (bool) $event->require_guest_name,
                'galleryVisible' => $event->isGalleryVisible(),
            ],
        ]);
    }

    /** Daftar tamu baru dan kirim token perangkatnya lewat cookie. */
    public function register(Request $request, Event $event): JsonResponse
    {
        if (! $event->isCaptureOpen()) {
            return response()->json(['message' => $event->captureClosedReason()], 422);
        }

        if ($existing = $this->guest($request, $event)) {
            return response()->json([
                'guest' => ['name' => $existing->name],
                'quota' => $existing->quotaSummary(),
            ]);
        }

        $data = $request->validate([
            'name' => [$event->require_guest_name ? 'required' : 'nullable', 'string', 'max:40'],
        ]);

        try {
            $guest = $this->captures->registerGuest($event, $data['name'] ?? 'Tamu', $request);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()
            ->json([
                'guest' => ['name' => $guest->name],
                'quota' => $guest->quotaSummary(),
            ])
            ->withCookie(Cookie::make(
                CaptureService::cookieName($event),
                $guest->token,
                60 * 24 * 365,
                null,
                null,
                $request->secure(),
                true,
                false,
                'Lax'
            ));
    }

    public function quota(Request $request, Event $event): JsonResponse
    {
        $guest = $this->guest($request, $event);

        if (! $guest) {
            return response()->json(['message' => 'Sesi tamu belum dibuat.'], 404);
        }

        return response()->json([
            'guest' => ['name' => $guest->name],
            'quota' => $guest->quotaSummary(),
            'open' => $event->isCaptureOpen(),
        ]);
    }

    /** Terima satu tangkapan dari kamera. */
    public function capture(Request $request, Event $event): JsonResponse
    {
        $guest = $this->guest($request, $event);

        if (! $guest) {
            return response()->json(['message' => 'Sesi tamu tidak ditemukan. Muat ulang halaman.'], 419);
        }

        $limits = config('setsuna.upload');

        $data = $request->validate([
            'type' => ['required', 'in:photo,video,boomerang'],
            'file' => ['required', 'file', 'max:' . $limits['video_max_kb']],
            'poster' => ['nullable', 'image', 'max:' . $limits['poster_max_kb']],
            'duration_ms' => ['nullable', 'integer', 'min:0', 'max:60000'],
            'facing' => ['nullable', 'in:user,environment'],
            'film_preset' => ['nullable', 'string', 'max:40'],
        ]);

        $file = $request->file('file');
        $maxKb = match ($data['type']) {
            'photo' => $limits['photo_max_kb'],
            'boomerang' => $limits['boomerang_max_kb'],
            default => $limits['video_max_kb'],
        };

        if ($file->getSize() > $maxKb * 1024) {
            return response()->json(['message' => 'Berkas terlalu besar untuk diunggah.'], 422);
        }

        if ($data['type'] === 'photo' && ! str_starts_with((string) $file->getMimeType(), 'image/')) {
            return response()->json(['message' => 'Berkas foto tidak valid.'], 422);
        }

        if ($data['type'] !== 'photo' && ! str_starts_with((string) $file->getMimeType(), 'video/')) {
            return response()->json(['message' => 'Berkas video tidak valid.'], 422);
        }

        try {
            $media = $this->captures->store(
                $event,
                $guest,
                $data['type'],
                $file,
                $request->file('poster'),
                [
                    'duration_ms' => $data['duration_ms'] ?? null,
                    'facing' => $data['facing'] ?? null,
                    'film_preset' => $data['film_preset'] ?? null,
                ]
            );
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'quota' => $guest->fresh()->quotaSummary(),
            ], 422);
        }

        return response()->json([
            'media' => $this->mediaPayload($media),
            'quota' => $guest->fresh()->quotaSummary(),
        ], 201);
    }

    /** Roll pribadi tamu, dipakai panel "Rollmu" di kamera. */
    public function roll(Request $request, Event $event): JsonResponse
    {
        $guest = $this->guest($request, $event);

        if (! $guest) {
            return response()->json(['media' => []]);
        }

        $media = $guest->media()
            ->whereIn('status', ['approved', 'pending'])
            ->latest()
            ->limit(60)
            ->get()
            ->map(fn ($item) => $this->mediaPayload($item));

        return response()->json([
            'media' => $media,
            'quota' => $guest->quotaSummary(),
        ]);
    }

    // ------------------------------------------------------------------

    private function guest(Request $request, Event $event): ?EventGuest
    {
        return $this->captures->findGuest(
            $event,
            $request->cookie(CaptureService::cookieName($event))
        );
    }

    /** @return array<string,mixed> */
    private function mediaPayload($media): array
    {
        return [
            'id' => $media->id,
            'type' => $media->type,
            'url' => $media->url(),
            'preview' => $media->previewUrl(),
            'duration_ms' => $media->duration_ms,
            'created_at' => $media->created_at->toIso8601String(),
        ];
    }
}
