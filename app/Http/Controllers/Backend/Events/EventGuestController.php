<?php

namespace App\Http\Controllers\Backend\Events;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventGuest;
use App\Support\ActivityRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\View\View;

/**
 * Daftar tamu satu acara: pemakaian kuota, tambah jatah, blokir.
 */
class EventGuestController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['auth', 'can:view_resources'];
    }

    public function index(Request $request, Event $event): View
    {
        $guests = $event->guests()
            ->withCount('media')
            ->when($request->filled('q'), fn ($query) => $query->where('name', 'ILIKE', '%' . $request->query('q') . '%'))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('backend.events.event.guests', [
            'event' => $event,
            'guests' => $guests,
        ]);
    }

    /** Tambah jatah tangkapan untuk satu tamu. */
    public function grant(Request $request, Event $event, EventGuest $guest): RedirectResponse
    {
        abort_unless($guest->event_id === $event->id, 404);

        $data = $request->validate([
            'photo' => ['nullable', 'integer', 'min:0', 'max:200'],
            'video' => ['nullable', 'integer', 'min:0', 'max:50'],
            'boomerang' => ['nullable', 'integer', 'min:0', 'max:50'],
        ]);

        $guest->increment('photo_quota', (int) ($data['photo'] ?? 0));
        $guest->increment('video_quota', (int) ($data['video'] ?? 0));
        $guest->increment('boomerang_quota', (int) ($data['boomerang'] ?? 0));

        ActivityRecorder::updated(
            'tambah kuota tamu',
            'Menambah kuota tamu ' . $guest->name . ' di acara ' . $event->title,
            [],
            $data,
            $event
        );

        return back()->with('success', 'Kuota ' . $guest->name . ' ditambah.');
    }

    public function toggleBlock(Event $event, EventGuest $guest): RedirectResponse
    {
        abort_unless($guest->event_id === $event->id, 404);

        $guest->update(['is_blocked' => ! $guest->is_blocked]);

        return back()->with('success', $guest->is_blocked
            ? $guest->name . ' diblokir dari memotret.'
            : $guest->name . ' bisa memotret lagi.');
    }

    public function destroy(Event $event, EventGuest $guest): RedirectResponse
    {
        abort_unless($guest->event_id === $event->id, 404);

        foreach ($guest->media as $media) {
            $media->deleteFiles();
            $media->forceDelete();
        }

        $snapshot = $guest->toArray();
        $guest->delete();

        ActivityRecorder::deleted('hapus tamu', 'Menghapus tamu ' . $snapshot['name'] . ' dari acara ' . $event->title, $snapshot);

        return back()->with('success', 'Tamu dan seluruh jepretannya dihapus.');
    }
}
