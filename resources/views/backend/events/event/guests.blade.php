@extends('backend.layout.app')
@section('title', 'Tamu · ' . $event->title)

@section('content')

    @include('backend.events._header', [
        'heading' => 'Tamu — ' . $event->title,
        'crumbs' => ['Manajemen Acara', 'Tamu'],
    ])

    <div class="app-content flex-column-fluid">
        <div class="app-container container-xxl">

            <div class="card card-flush">
                <div class="card-header py-6">
                    <div class="card-title">
                        <form method="GET" class="d-flex align-items-center position-relative">
                            <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                            <input type="text" name="q" value="{{ request('q') }}"
                                class="form-control form-control-solid w-250px ps-13" placeholder="Cari nama tamu" />
                        </form>
                    </div>
                    <div class="card-toolbar">
                        <a href="{{ route('events.show', $event) }}" class="btn btn-sm btn-light">← Detail acara</a>
                    </div>
                </div>

                <div class="card-body pt-0">
                    @if ($guests->count())
                        <table class="table align-middle table-row-dashed fs-6 gy-4">
                            <thead>
                                <tr class="text-muted fw-bold fs-8 text-uppercase">
                                    <th class="min-w-150px">Tamu</th>
                                    <th>Terpakai</th>
                                    <th>Sisa jatah</th>
                                    <th>Terakhir aktif</th>
                                    <th class="text-end min-w-250px">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($guests as $person)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="symbol symbol-circle symbol-35px me-3">
                                                    <div class="symbol-label fw-bold"
                                                        style="background:{{ $person->color }}22;color:{{ $person->color }}">
                                                        {{ $person->initials }}
                                                    </div>
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-gray-900">
                                                        {{ $person->name }}
                                                        @if ($person->is_blocked)
                                                            <span class="badge badge-light-danger ms-1">Diblokir</span>
                                                        @endif
                                                    </div>
                                                    <div class="text-muted fs-8">{{ $person->device ?? 'perangkat tidak dikenal' }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="fw-bold">{{ $person->media_count }}</td>
                                        <td class="text-muted fs-8">
                                            {{ $person->remaining('photo') }} foto ·
                                            {{ $person->remaining('video') }} video ·
                                            {{ $person->remaining('boomerang') }} boom
                                        </td>
                                        <td class="text-muted fs-8">{{ $person->last_active_at?->diffForHumans() ?? '-' }}</td>
                                        <td>
                                            <div class="d-flex justify-content-end gap-1 flex-wrap">
                                                <form method="POST" action="{{ route('events.guests.grant', [$event, $person]) }}"
                                                    class="d-flex gap-1">
                                                    @csrf
                                                    <input type="number" name="photo" min="0" max="200" value="5"
                                                        class="form-control form-control-sm w-60px" title="Tambah foto" />
                                                    <button class="btn btn-sm btn-light-success" title="Tambah jatah foto">
                                                        <i class="ki-outline ki-plus fs-5"></i>
                                                    </button>
                                                </form>

                                                <form method="POST" action="{{ route('events.guests.block', [$event, $person]) }}">
                                                    @csrf
                                                    <button class="btn btn-sm btn-light-warning">
                                                        {{ $person->is_blocked ? 'Buka blokir' : 'Blokir' }}
                                                    </button>
                                                </form>

                                                <form method="POST" action="{{ route('events.guests.destroy', [$event, $person]) }}"
                                                    data-confirm="Hapus {{ $person->name }} beserta semua jepretannya?">
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-icon btn-sm btn-light-danger">
                                                        <i class="ki-outline ki-trash fs-5"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="mt-4">{{ $guests->links() }}</div>
                    @else
                        <div class="text-center text-muted py-15">Belum ada tamu yang scan QR acara ini.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection
