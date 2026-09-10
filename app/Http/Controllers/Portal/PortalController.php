<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventGuest;
use App\Models\EventMedia;
use App\Services\CaptureService;
use App\Support\FilmPresets;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Portal publik milik satu acara: halaman sambutan, galeri bersama,
 * dan roll pribadi tiap tamu. Semua di bawah slug acara.
 */
class PortalController extends Controller
{
    public function __construct(private readonly CaptureService $captures)
    {
    }

    /** Halaman sambutan acara. */
    public function show(Request $request, Event $event): View
    {
        $event->increment('views_count');

        $guest = $this->guest($request, $event);

        return view('portal.show', [
            'event' => $event,
            'guest' => $guest,
            'preset' => FilmPresets::get($event->film_preset),
            'counts' => $this->counts($event),
            'galleryVisible' => $event->isGalleryVisible(),
        ]);
    }

    /** Galeri bersama, terbuka sesuai aturan reveal acara. */
    public function gallery(Request $request, Event $event): View
    {
        $guest = $this->guest($request, $event);

        if (! $event->isGalleryVisible()) {
            return view('portal.locked', [
                'event' => $event,
                'guest' => $guest,
                'reason' => $event->galleryHiddenReason(),
                'counts' => $this->counts($event),
            ]);
        }

        $type = $request->query('type');

        $media = $event->media()
            ->visible()
            ->with('guest:id,name,color')
            ->when(in_array($type, EventMedia::TYPES, true), fn ($query) => $query->where('type', $type))
            ->when($request->filled('guest'), fn ($query) => $query->where('event_guest_id', $request->query('guest')))
            ->latest('captured_at')
            ->paginate(48)
            ->withQueryString();

        return view('portal.gallery', [
            'event' => $event,
            'guest' => $guest,
            'media' => $media,
            'counts' => $this->counts($event),
            // Postgres tidak bisa memfilter alias withCount di HAVING,
            // jadi tamu tanpa jepretan disaring lewat whereHas.
            'contributors' => $event->guests()
                ->withCount(['media' => fn ($query) => $query->where('status', 'approved')])
                ->whereHas('media', fn ($query) => $query->where('status', 'approved'))
                ->orderByDesc('media_count')
                ->get(),
            'activeType' => in_array($type, EventMedia::TYPES, true) ? $type : null,
        ]);
    }

    /** Roll pribadi: hasil jepretan perangkat ini saja. */
    public function roll(Request $request, Event $event): View
    {
        $guest = $this->guest($request, $event);

        $media = $guest
            ? $guest->media()->whereIn('status', ['approved', 'pending'])->latest()->get()
            : collect();

        return view('portal.roll', [
            'event' => $event,
            'guest' => $guest,
            'media' => $media,
            'galleryVisible' => $event->isGalleryVisible(),
        ]);
    }

    /** Album satu tamu, dibuka dari galeri bersama. */
    public function guestAlbum(Request $request, Event $event, EventGuest $guest): View
    {
        abort_unless($guest->event_id === $event->id, 404);
        abort_unless($event->isGalleryVisible(), 403, $event->galleryHiddenReason());

        return view('portal.guest', [
            'event' => $event,
            'album' => $guest,
            'guest' => $this->guest($request, $event),
            'media' => $guest->media()->visible()->latest('captured_at')->get(),
        ]);
    }

    /**
     * Umpan media terbaru untuk album dan roll.
     *
     * Halaman memanggil ini berkala supaya jepretan yang baru masuk
     * langsung muncul tanpa tamu perlu memuat ulang — tidak menunggu
     * jatah seseorang habis.
     */
    public function feed(Request $request, Event $event): JsonResponse
    {
        $viewer = $this->guest($request, $event);
        $scope = $request->query('scope') === 'mine' ? 'mine' : 'all';

        if ($scope === 'mine') {
            if (! $viewer) {
                return response()->json(['media' => []]);
            }

            $query = $viewer->media()->whereIn('status', ['approved', 'pending']);
        } else {
            if (! $event->isGalleryVisible()) {
                return response()->json(['media' => [], 'locked' => true]);
            }

            $query = $event->media()->visible()->with('guest:id,name');
        }

        // Hanya yang lebih baru dari yang sudah dipegang halaman.
        // Penandanya UTC berakhiran "Z": tanda "+" pada offset zona akan
        // berubah jadi spasi di query string dan membuat parse gagal.
        if ($since = $request->query('since')) {
            try {
                $query->where('created_at', '>', Carbon::parse($since)->setTimezone(config('app.timezone')));
            } catch (\Throwable) {
                // Penanda waktu tidak terbaca: perlakukan sebagai permintaan awal.
            }
        }

        $media = $query->latest('created_at')->limit(40)->get();

        return response()->json([
            'media' => $media->map(fn (EventMedia $item) => [
                'id' => $item->id,
                'type' => $item->type,
                'url' => $item->url(),
                'preview' => $item->previewUrl(),
                'by' => $item->guest?->name ?? 'Tamu',
                'preset' => FilmPresets::css($item->film_preset ?: $event->film_preset),
                'download' => $event->allow_download ? $item->downloadUrl() : null,
                'created_at' => $item->created_at->toIso8601String(),
            ]),
            'server_time' => now()->utc()->format('Y-m-d\TH:i:s.v\Z'),
        ]);
    }

    /** Unduh satu berkas asli. */
    public function download(Request $request, Event $event, EventMedia $media): StreamedResponse
    {
        abort_unless($media->event_id === $event->id, 404);

        $viewer = $this->guest($request, $event);
        $isOwner = $viewer && $viewer->id === $media->event_guest_id;

        abort_unless($event->allow_download || $isOwner, 403, 'Unduhan dinonaktifkan untuk acara ini.');
        abort_unless($isOwner || ($event->isGalleryVisible() && $media->status === 'approved'), 403);

        $disk = Storage::disk($media->disk);
        abort_unless($disk->exists($media->path), 404);

        $media->increment('downloads_count');

        $name = $event->slug . '-' . substr($media->id, 0, 8) . '.' . pathinfo($media->path, PATHINFO_EXTENSION);

        return $disk->download($media->path, $name);
    }

    // ------------------------------------------------------------------

    private function guest(Request $request, Event $event): ?EventGuest
    {
        return $this->captures->findGuest(
            $event,
            $request->cookie(CaptureService::cookieName($event))
        );
    }

    /** @return array<string,int> */
    private function counts(Event $event): array
    {
        $rows = $event->media()
            ->visible()
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        return [
            'photo' => (int) ($rows['photo'] ?? 0),
            'video' => (int) ($rows['video'] ?? 0),
            'boomerang' => (int) ($rows['boomerang'] ?? 0),
            'total' => (int) $rows->sum(),
            'guests' => $event->guests()->count(),
        ];
    }
}
