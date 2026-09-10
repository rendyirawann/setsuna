<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventGuest;
use App\Models\EventMedia;
use App\Services\CaptureService;
use App\Support\FilmPresets;
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
