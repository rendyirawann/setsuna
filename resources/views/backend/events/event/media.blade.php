@extends('backend.layout.app')
@section('title', 'Media · ' . $event->title)

@section('content')

    @include('backend.events._header', [
        'heading' => 'Media — ' . $event->title,
        'crumbs' => ['Manajemen Acara', 'Media'],
    ])

    <div class="app-content flex-column-fluid">
        <div class="app-container container-xxl">

            <div class="card card-flush">
                <div class="card-header py-6 flex-wrap gap-3">
                    <div class="card-title flex-wrap gap-2">
                        @foreach ([
                            '' => 'Semua (' . $tally['all'] . ')',
                            'pending' => 'Perlu moderasi (' . $tally['pending'] . ')',
                            'hidden' => 'Disembunyikan (' . $tally['hidden'] . ')',
                        ] as $value => $label)
                            <a href="{{ route('events.media', ['event' => $event, 'status' => $value ?: null, 'type' => $activeType]) }}"
                                class="btn btn-sm {{ $activeStatus === ($value ?: null) ? 'btn-primary' : 'btn-light' }}">
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>

                    <div class="card-toolbar gap-2">
                        <a href="{{ route('events.show', $event) }}" class="btn btn-sm btn-light">← Detail acara</a>
                        <a href="{{ route('events.download-all', $event) }}" class="btn btn-sm btn-light-info">
                            <i class="ki-outline ki-file-down fs-5"></i> Unduh semua
                        </a>
                    </div>
                </div>

                <div class="card-body pt-0">

                    {{-- Saring jenis & tamu --}}
                    <div class="d-flex flex-wrap gap-2 mb-5">
                        <a href="{{ route('events.media', ['event' => $event, 'status' => $activeStatus]) }}"
                            class="btn btn-sm {{ $activeType ? 'btn-light' : 'btn-light-primary' }}">Semua jenis</a>
                        @foreach (['photo' => 'Foto', 'video' => 'Video', 'boomerang' => 'Boomerang'] as $type => $label)
                            <a href="{{ route('events.media', ['event' => $event, 'type' => $type, 'status' => $activeStatus]) }}"
                                class="btn btn-sm {{ $activeType === $type ? 'btn-light-primary' : 'btn-light' }}">{{ $label }}</a>
                        @endforeach

                        <form method="GET" class="ms-auto d-flex gap-2">
                            <input type="hidden" name="status" value="{{ $activeStatus }}" />
                            <input type="hidden" name="type" value="{{ $activeType }}" />
                            <select name="guest" class="form-select form-select-sm form-select-solid w-200px"
                                onchange="this.form.submit()">
                                <option value="">Semua tamu</option>
                                @foreach ($guests as $person)
                                    <option value="{{ $person->id }}" @selected(request('guest') === $person->id)>
                                        {{ $person->name }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    </div>

                    @if ($media->count())
                        <form method="POST" action="{{ route('events.media.bulk', $event) }}" id="bulk-form">
                            @csrf

                            <div class="d-flex align-items-center gap-2 mb-4">
                                <label class="form-check form-check-sm form-check-custom">
                                    <input class="form-check-input" type="checkbox" id="check-all" />
                                    <span class="form-check-label ms-2">Pilih semua di halaman ini</span>
                                </label>

                                <div class="ms-auto d-flex gap-2">
                                    <button name="action" value="approve" class="btn btn-sm btn-light-success">Setujui</button>
                                    <button name="action" value="hide" class="btn btn-sm btn-light-warning">Sembunyikan</button>
                                    <button name="action" value="delete" class="btn btn-sm btn-light-danger"
                                        data-confirm="Hapus permanen media terpilih?">Hapus</button>
                                </div>
                            </div>

                            <div class="row g-4">
                                @foreach ($media as $item)
                                    <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                                        <div class="card card-flush h-100 border">
                                            <div class="position-relative">
                                                <img src="{{ $item->previewUrl() }}" class="rounded-top w-100"
                                                    style="height:190px;object-fit:cover" alt="" loading="lazy" />

                                                <label class="position-absolute top-0 start-0 m-2">
                                                    <input class="form-check-input bulk-check" type="checkbox"
                                                        name="ids[]" value="{{ $item->id }}" />
                                                </label>

                                                @if ($item->type !== 'photo')
                                                    <span class="badge badge-dark position-absolute top-0 end-0 m-2 fs-9">
                                                        {{ $item->type_label }}
                                                    </span>
                                                @endif

                                                @if ($item->status !== 'approved')
                                                    <span class="badge badge-warning position-absolute bottom-0 end-0 m-2 fs-9">
                                                        {{ $item->status }}
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="card-body p-3">
                                                <div class="fw-bold fs-8 text-gray-800">{{ $item->guest?->name ?? 'Tamu' }}</div>
                                                <div class="text-muted fs-9 mb-2">
                                                    {{ $item->captured_at?->diffForHumans() }} · {{ $item->size_label }}
                                                </div>

                                                <div class="d-flex gap-1">
                                                    <a href="{{ $item->url() }}" target="_blank"
                                                        class="btn btn-icon btn-sm btn-light" title="Lihat">
                                                        <i class="ki-outline ki-eye fs-5"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </form>

                        <div class="mt-5">{{ $media->links() }}</div>
                    @else
                        <div class="text-center text-muted py-15">Tidak ada media pada filter ini.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        document.getElementById('check-all')?.addEventListener('change', function () {
            document.querySelectorAll('.bulk-check').forEach((box) => { box.checked = this.checked; });
        });
    </script>
@endpush
