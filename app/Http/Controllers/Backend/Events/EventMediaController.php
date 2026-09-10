<?php

namespace App\Http\Controllers\Backend\Events;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventMedia;
use App\Support\ActivityRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Moderasi hasil jepretan tamu untuk satu acara.
 */
class EventMediaController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['auth', 'can:view_resources'];
    }

    public function index(Request $request, Event $event): View
    {
        $status = $request->query('status');
        $type = $request->query('type');

        $media = $event->media()
            ->with('guest:id,name,color')
            ->when(in_array($status, ['approved', 'pending', 'hidden', 'rejected'], true), fn ($q) => $q->where('status', $status))
            ->when(in_array($type, EventMedia::TYPES, true), fn ($q) => $q->where('type', $type))
            ->when($request->filled('guest'), fn ($q) => $q->where('event_guest_id', $request->query('guest')))
            ->latest('captured_at')
            ->paginate(60)
            ->withQueryString();

        return view('backend.events.event.media', [
            'event' => $event,
            'media' => $media,
            'activeStatus' => $status,
            'activeType' => $type,
            'guests' => $event->guests()->orderBy('name')->get(['id', 'name']),
            'tally' => [
                'all' => $event->media()->count(),
                'pending' => $event->media()->where('status', 'pending')->count(),
                'hidden' => $event->media()->where('status', 'hidden')->count(),
            ],
        ]);
    }

    /** Ubah status satu media. */
    public function status(Request $request, Event $event, EventMedia $medium): RedirectResponse
    {
        abort_unless($medium->event_id === $event->id, 404);

        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'pending', 'hidden', 'rejected'])],
        ]);

        $medium->update($data);

        return back()->with('success', 'Status media diperbarui.');
    }

    public function feature(Event $event, EventMedia $medium): RedirectResponse
    {
        abort_unless($medium->event_id === $event->id, 404);

        $medium->update(['is_featured' => ! $medium->is_featured]);

        return back()->with('success', $medium->is_featured ? 'Media disorot di galeri.' : 'Sorotan dilepas.');
    }

    public function destroy(Event $event, EventMedia $medium): RedirectResponse
    {
        abort_unless($medium->event_id === $event->id, 404);

        $snapshot = $medium->only(['id', 'type', 'path', 'event_guest_id']);
        $medium->deleteFiles();
        $medium->forceDelete();

        ActivityRecorder::deleted('hapus media', 'Menghapus media acara ' . $event->title, $snapshot);

        return back()->with('success', 'Media dihapus permanen.');
    }

    /** Aksi massal dari grid moderasi. */
    public function bulk(Request $request, Event $event): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['approve', 'hide', 'delete'])],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['uuid'],
        ]);

        $items = $event->media()->whereIn('id', $data['ids'])->get();

        foreach ($items as $item) {
            match ($data['action']) {
                'approve' => $item->update(['status' => 'approved']),
                'hide' => $item->update(['status' => 'hidden']),
                'delete' => tap($item, fn ($media) => $media->deleteFiles())->forceDelete(),
            };
        }

        ActivityRecorder::updated(
            'moderasi media',
            'Aksi massal ' . $data['action'] . ' pada ' . $items->count() . ' media acara ' . $event->title,
            [],
            ['action' => $data['action'], 'count' => $items->count()],
            $event
        );

        return back()->with('success', $items->count() . ' media diproses.');
    }
}
