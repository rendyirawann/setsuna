<?php

namespace App\Http\Controllers\Backend\Events;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Event;
use App\Models\Plan;
use App\Support\ActivityRecorder;
use App\Support\FilmPresets;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Yajra\DataTables\Facades\DataTables;
use ZipArchive;

/**
 * Acara pernikahan: portal, QR, kuota, dan status buka/tutup kamera.
 */
class EventController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['auth', 'can:view_resources'];
    }

    public function index(): View
    {
        return view('backend.events.event.index', [
            'stats' => [
                'active' => Event::where('status', 'active')->count(),
                'draft' => Event::where('status', 'draft')->count(),
                'upcoming' => Event::whereDate('event_date', '>=', today())->whereIn('status', ['active', 'draft'])->count(),
                'media' => \App\Models\EventMedia::count(),
            ],
        ]);
    }

    public function getData(Request $request): JsonResponse
    {
        $query = Event::with('client:id,name', 'plan:id,name')
            ->withCount(['guests', 'media'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->latest('created_at');

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('event', fn (Event $event) => view('backend.events.event._name', compact('event'))->render())
            ->addColumn('schedule', fn (Event $event) => $event->event_date
                ? $event->event_date->translatedFormat('d M Y') . '<br><span class="text-muted fs-8">' . e($event->venue ?: '-') . '</span>'
                : '<span class="text-muted">Belum dijadwalkan</span>')
            ->addColumn('usage', fn (Event $event) => $event->guests_count . ' tamu · ' . $event->media_count . ' media')
            ->addColumn('status', fn (Event $event) => '<span class="badge badge-light-' . $event->status_badge . '">' . $event->status_label . '</span>')
            ->addColumn('action', fn (Event $event) => view('backend.events.event._action', compact('event'))->render())
            ->rawColumns(['event', 'schedule', 'status', 'action'])
            ->make(true);
    }

    public function create(): View
    {
        $defaults = config('setsuna.defaults');

        return view('backend.events.event.form', [
            'event' => new Event([
                'photo_quota' => $defaults['photo_quota'],
                'video_quota' => $defaults['video_quota'],
                'video_duration' => $defaults['video_duration'],
                'boomerang_quota' => $defaults['boomerang_quota'],
                'film_preset' => $defaults['film_preset'],
                'gallery_reveal' => $defaults['gallery_reveal'],
                'status' => 'draft',
                'gallery_public' => true,
                'require_guest_name' => true,
                'allow_download' => true,
            ]),
            'clients' => Client::orderBy('name')->get(),
            'plans' => Plan::orderBy('sort_order')->get(),
            'presets' => FilmPresets::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $event = Event::create($this->validated($request));

        ActivityRecorder::created('tambah acara', 'Membuat acara ' . $event->title, $event->toArray(), $event);

        return redirect()->route('events.show', $event)->with('success', 'Acara berhasil dibuat. Cetak QR-nya lalu aktifkan acara.');
    }

    public function show(Event $event): View
    {
        $event->load('client', 'plan');

        return view('backend.events.event.show', [
            'event' => $event,
            'counts' => $this->counts($event),
            'recent' => $event->media()->with('guest:id,name')->latest()->limit(12)->get(),
            'topGuests' => $event->guests()->withCount('media')->orderByDesc('media_count')->limit(8)->get(),
            'subscription' => $event->subscriptions()->latest()->first(),
        ]);
    }

    public function edit(Event $event): View
    {
        return view('backend.events.event.form', [
            'event' => $event,
            'clients' => Client::orderBy('name')->get(),
            'plans' => Plan::orderBy('sort_order')->get(),
            'presets' => FilmPresets::options(),
        ]);
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $old = $event->toArray();
        $event->update($this->validated($request, $event));

        ActivityRecorder::updated('edit acara', 'Mengubah acara ' . $event->title, $old, $event->toArray(), $event);

        return redirect()->route('events.show', $event)->with('success', 'Acara berhasil diperbarui.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        $snapshot = $event->toArray();
        $event->delete();

        ActivityRecorder::deleted('hapus acara', 'Menghapus acara ' . $snapshot['title'], $snapshot);

        return redirect()->route('events.index')->with('success', 'Acara berhasil dihapus.');
    }

    // ------------------------------------------------------------------
    // Aksi cepat
    // ------------------------------------------------------------------

    /** Ubah status acara (buka/jeda/selesaikan kamera). */
    public function status(Request $request, Event $event): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(Event::STATUSES)],
        ]);

        $old = $event->status;
        $event->update(['status' => $data['status']]);

        ActivityRecorder::updated(
            'status acara',
            'Mengubah status acara ' . $event->title,
            ['status' => $old],
            ['status' => $event->status],
            $event
        );

        return back()->with('success', 'Status acara sekarang: ' . $event->status_label . '.');
    }

    /** Buka galeri bersama lebih awal (mode manual / kejutan). */
    public function reveal(Event $event): RedirectResponse
    {
        $event->update(['gallery_unlocked' => ! $event->gallery_unlocked]);

        return back()->with('success', $event->gallery_unlocked
            ? 'Galeri bersama sekarang terbuka untuk tamu.'
            : 'Galeri bersama kembali dikunci.');
    }

    /** Ganti token QR — QR lama langsung tidak berlaku. */
    public function rotateQr(Event $event): RedirectResponse
    {
        $event->update(['qr_token' => Str::lower(Str::random(24))]);

        ActivityRecorder::updated('rotasi qr', 'Mengganti QR acara ' . $event->title, [], ['qr_token' => 'diganti'], $event);

        return back()->with('success', 'QR baru dibuat. Cetak ulang papan QR di venue.');
    }

    /** Halaman papan QR siap cetak untuk dipajang di venue. */
    public function sign(Event $event): View
    {
        return view('backend.events.event.sign', [
            'event' => $event,
            'qr' => $this->qrSvg($event, 520),
        ]);
    }

    /** Unduh QR sebagai SVG (aman diperbesar untuk cetak). */
    public function qrDownload(Event $event): Response
    {
        return response($this->qrSvg($event, 1000), 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'attachment; filename="qr-' . $event->slug . '.svg"',
        ]);
    }

    /** Unduh semua media acara dalam satu zip. */
    public function downloadAll(Event $event)
    {
        $media = $event->media()->where('status', 'approved')->get();

        if ($media->isEmpty()) {
            return back()->with('error', 'Belum ada media untuk diunduh.');
        }

        $zipName = 'setsuna-' . $event->slug . '-' . now()->format('Ymd-His') . '.zip';
        $zipPath = storage_path('app/tmp/' . $zipName);

        if (! is_dir(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0o755, true);
        }

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Tidak bisa membuat arsip zip.');
        }

        foreach ($media as $item) {
            $disk = Storage::disk($item->disk);

            if (! $disk->exists($item->path)) {
                continue;
            }

            $guest = Str::slug($item->guest?->name ?: 'tamu');
            $zip->addFile(
                $disk->path($item->path),
                $item->type . '/' . $guest . '-' . substr($item->id, 0, 8) . '.' . pathinfo($item->path, PATHINFO_EXTENSION)
            );
        }

        $zip->close();

        return response()->download($zipPath, $zipName)->deleteFileAfterSend();
    }

    // ------------------------------------------------------------------

    private function qrSvg(Event $event, int $size): string
    {
        return (string) QrCode::format('svg')
            ->size($size)
            ->margin(1)
            ->errorCorrection('H')
            ->generate($event->cameraUrl());
    }

    /** @return array<string,int> */
    private function counts(Event $event): array
    {
        $rows = $event->media()->selectRaw('type, count(*) as total')->groupBy('type')->pluck('total', 'type');

        return [
            'photo' => (int) ($rows['photo'] ?? 0),
            'video' => (int) ($rows['video'] ?? 0),
            'boomerang' => (int) ($rows['boomerang'] ?? 0),
            'total' => (int) $rows->sum(),
            'guests' => $event->guests()->count(),
            'scans' => (int) $event->scans_count,
            'pending' => $event->media()->where('status', 'pending')->count(),
        ];
    }

    /** @return array<string,mixed> */
    private function validated(Request $request, ?Event $event = null): array
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'plan_id' => ['nullable', 'exists:plans,id'],
            'slug' => [
                'required', 'string', 'max:60', 'regex:/^[a-z0-9][a-z0-9\-]*$/',
                Rule::unique('events', 'slug')->ignore($event?->id),
                Rule::notIn(config('setsuna.reserved_slugs')),
            ],
            'title' => ['required', 'string', 'max:160'],
            'event_type' => ['required', Rule::in(array_keys(Event::TYPES))],
            'bride_name' => ['nullable', 'string', 'max:80'],
            'groom_name' => ['nullable', 'string', 'max:80'],
            'host_name' => ['nullable', 'string', 'max:120'],
            'hashtag' => ['nullable', 'string', 'max:60'],
            'event_date' => ['nullable', 'date'],
            'venue' => ['nullable', 'string', 'max:160'],
            'city' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:500'],
            'cover' => ['nullable', 'image', 'max:4096'],
            'welcome_message' => ['nullable', 'string', 'max:1000'],
            'sign_message' => ['nullable', 'string', 'max:500'],
            'film_preset' => ['required', Rule::in(array_keys(FilmPresets::all()))],
            'photo_quota' => ['required', 'integer', 'min:1', 'max:500'],
            'video_quota' => ['required', 'integer', 'min:0', 'max:100'],
            'video_duration' => ['required', 'integer', 'min:3', 'max:60'],
            'boomerang_quota' => ['required', 'integer', 'min:0', 'max:100'],
            'max_guests' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'gallery_reveal' => ['required', Rule::in(['instant', 'after_event', 'manual'])],
            'gallery_password' => ['nullable', 'string', 'max:60'],
            'capture_opens_at' => ['nullable', 'date'],
            'capture_closes_at' => ['nullable', 'date', 'after:capture_opens_at'],
            'media_expires_at' => ['nullable', 'date'],
            'status' => ['required', Rule::in(Event::STATUSES)],
        ], [
            'slug.regex' => 'Alamat portal hanya boleh huruf kecil, angka, dan tanda hubung.',
            'slug.not_in' => 'Alamat portal ini dipakai sistem, pilih yang lain.',
        ]);

        $data['gallery_public'] = $request->boolean('gallery_public');
        $data['require_guest_name'] = $request->boolean('require_guest_name');
        $data['allow_download'] = $request->boolean('allow_download');
        $data['moderation'] = $request->boolean('moderation');

        if ($request->hasFile('cover')) {
            if ($event?->cover_path) {
                Storage::disk('public')->delete($event->cover_path);
            }

            $data['cover_path'] = $request->file('cover')->store('events/' . $data['slug'] . '/cover', 'public');
        }

        unset($data['cover']);

        return $data;
    }
}
