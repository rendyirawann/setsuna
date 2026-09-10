@extends('backend.layout.app')
@section('title', $event->title)

@section('content')

    @include('backend.events._header', [
        'heading' => $event->title,
        'crumbs' => ['Manajemen Acara', 'Detail'],
    ])

    <div class="app-content flex-column-fluid">
        <div class="app-container container-xxl">

            <div class="row g-5">

                {{-- --------------------------------------------- Kolom kiri --}}
                <div class="col-lg-8">

                    {{-- Ringkasan angka --}}
                    <div class="row g-4 mb-5">
                        @foreach ([
                            ['Tamu', $counts['guests'], 'ki-people', 'primary'],
                            ['Foto', $counts['photo'], 'ki-picture', 'success'],
                            ['Video', $counts['video'], 'ki-youtube', 'info'],
                            ['Boomerang', $counts['boomerang'], 'ki-arrows-circle', 'warning'],
                            ['Scan QR', $counts['scans'], 'ki-scan-barcode', 'dark'],
                            ['Perlu moderasi', $counts['pending'], 'ki-shield-search', 'danger'],
                        ] as [$label, $value, $icon, $color])
                            <div class="col-6 col-md-4">
                                <div class="card card-flush h-100">
                                    <div class="card-body py-5 d-flex align-items-center gap-3">
                                        <i class="ki-outline {{ $icon }} fs-2 text-{{ $color }}"></i>
                                        <div>
                                            <div class="fs-3 fw-bold text-gray-900">{{ number_format($value, 0, ',', '.') }}</div>
                                            <div class="fs-8 text-muted">{{ $label }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Jepretan terbaru --}}
                    <div class="card card-flush mb-5">
                        <div class="card-header">
                            <h3 class="card-title">Jepretan Terbaru</h3>
                            <div class="card-toolbar">
                                <a href="{{ route('events.media', $event) }}" class="btn btn-sm btn-light-primary">
                                    Kelola semua media
                                </a>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            @if ($recent->count())
                                <div class="d-flex flex-wrap gap-3">
                                    @foreach ($recent as $item)
                                        <div class="position-relative" style="width:110px">
                                            <img src="{{ $item->previewUrl() }}" class="rounded w-100"
                                                style="height:145px;object-fit:cover" alt="" loading="lazy" />
                                            <span class="badge badge-dark position-absolute bottom-0 start-0 m-1 fs-9">
                                                {{ $item->guest?->name ?? 'Tamu' }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-muted text-center py-10">
                                    Belum ada jepretan masuk. Pastikan acara berstatus aktif dan papan QR sudah terpasang.
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Tamu paling aktif --}}
                    <div class="card card-flush">
                        <div class="card-header">
                            <h3 class="card-title">Tamu Paling Aktif</h3>
                            <div class="card-toolbar">
                                <a href="{{ route('events.guests', $event) }}" class="btn btn-sm btn-light">Semua tamu</a>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            @if ($topGuests->count())
                                <table class="table align-middle table-row-dashed fs-6 gy-3 mb-0">
                                    <thead>
                                        <tr class="text-muted fw-bold fs-8 text-uppercase">
                                            <th>Nama</th>
                                            <th>Jepretan</th>
                                            <th>Sisa jatah</th>
                                            <th>Terakhir aktif</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($topGuests as $person)
                                            <tr>
                                                <td class="fw-bold text-gray-900">{{ $person->name }}</td>
                                                <td>{{ $person->media_count }}</td>
                                                <td class="text-muted fs-8">
                                                    {{ $person->remaining('photo') }} foto ·
                                                    {{ $person->remaining('video') }} video ·
                                                    {{ $person->remaining('boomerang') }} boom
                                                </td>
                                                <td class="text-muted fs-8">
                                                    {{ $person->last_active_at?->diffForHumans() ?? '-' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <div class="text-muted text-center py-10">Belum ada tamu yang scan QR.</div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- -------------------------------------------- Kolom kanan --}}
                <div class="col-lg-4">

                    <div class="card card-flush mb-5">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <span class="badge badge-light-{{ $event->status_badge }} fs-7">{{ $event->status_label }}</span>
                                <span class="text-muted fs-8">{{ $event->type_label }}</span>
                            </div>

                            <div class="d-flex flex-column gap-3 fs-7">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Klien</span>
                                    <a href="{{ route('clients.show', $event->client) }}" class="fw-bold">{{ $event->client->name }}</a>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Paket</span>
                                    <span class="fw-bold">{{ $event->plan?->name ?? '-' }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Tanggal</span>
                                    <span class="fw-bold">{{ $event->event_date?->translatedFormat('d M Y') ?? '-' }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Jatah tamu</span>
                                    <span class="fw-bold">
                                        {{ $event->photo_quota }}/{{ $event->video_quota }}/{{ $event->boomerang_quota }}
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Preset</span>
                                    <span class="fw-bold">{{ \App\Support\FilmPresets::name($event->film_preset) }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Album</span>
                                    <span class="fw-bold">
                                        {{ $event->isGalleryVisible() ? 'Terbuka' : 'Terkunci' }}
                                    </span>
                                </div>
                            </div>

                            <div class="separator my-4"></div>

                            <a href="{{ $event->portalUrl() }}" target="_blank" class="btn btn-light-primary btn-sm w-100 mb-2">
                                <i class="ki-outline ki-exit-right-corner fs-5"></i> Buka portal publik
                            </a>
                            <a href="{{ route('events.edit', $event) }}" class="btn btn-light btn-sm w-100">
                                <i class="ki-outline ki-pencil fs-5"></i> Ubah acara
                            </a>
                        </div>
                    </div>

                    {{-- Aksi cepat --}}
                    <div class="card card-flush mb-5">
                        <div class="card-header"><h3 class="card-title">Aksi Cepat</h3></div>
                        <div class="card-body pt-0 d-flex flex-column gap-2">

                            <form method="POST" action="{{ route('events.status', $event) }}" class="d-flex gap-2">
                                @csrf
                                <select name="status" class="form-select form-select-sm form-select-solid">
                                    @foreach (\App\Models\Event::STATUSES as $status)
                                        <option value="{{ $status }}" @selected($event->status === $status)>
                                            {{ ucfirst($status) }}
                                        </option>
                                    @endforeach
                                </select>
                                <button class="btn btn-sm btn-primary">Set</button>
                            </form>

                            <form method="POST" action="{{ route('events.reveal', $event) }}">
                                @csrf
                                <button class="btn btn-sm btn-light-success w-100">
                                    <i class="ki-outline ki-eye fs-5"></i>
                                    {{ $event->gallery_unlocked ? 'Kunci album lagi' : 'Buka album sekarang' }}
                                </button>
                            </form>

                            <a href="{{ route('events.download-all', $event) }}" class="btn btn-sm btn-light-info w-100">
                                <i class="ki-outline ki-file-down fs-5"></i> Unduh semua (.zip)
                            </a>

                            <form method="POST" action="{{ route('events.rotate-qr', $event) }}"
                                data-confirm="Ganti QR? QR lama langsung tidak berlaku dan papan harus dicetak ulang.">
                                @csrf
                                <button class="btn btn-sm btn-light-warning w-100">
                                    <i class="ki-outline ki-arrows-circle fs-5"></i> Ganti token QR
                                </button>
                            </form>

                            <form method="POST" action="{{ route('events.destroy', $event) }}"
                                data-confirm="Hapus acara {{ $event->title }} beserta portalnya?">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-light-danger w-100">
                                    <i class="ki-outline ki-trash fs-5"></i> Hapus acara
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- Pesanan terkait --}}
                    @if ($subscription)
                        <div class="card card-flush">
                            <div class="card-header"><h3 class="card-title">Pesanan</h3></div>
                            <div class="card-body pt-0 fs-7">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Invoice</span>
                                    <a href="{{ route('subscriptions.show', $subscription) }}" class="fw-bold">
                                        {{ $subscription->invoice_number }}
                                    </a>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Status</span>
                                    <span class="badge badge-light-{{ $subscription->status_badge }}">
                                        {{ $subscription->status_label }}
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Total</span>
                                    <span class="fw-bold">{{ $subscription->total_label }}</span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection
