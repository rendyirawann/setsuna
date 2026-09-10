@extends('backend.layout.app')
@section('title', 'Acara')

@section('content')

    @include('backend.events._header', [
        'heading' => 'Acara',
        'crumbs' => ['Manajemen Acara', 'Daftar Acara'],
    ])

    <div class="app-content flex-column-fluid">
        <div class="app-container container-xxl">

            {{-- ------------------------------------------------ Ringkasan --}}
            <div class="row g-5 mb-5">
                @foreach ([
                    ['Acara aktif', $stats['active'], 'ki-check-circle', 'success'],
                    ['Draft', $stats['draft'], 'ki-note-2', 'secondary'],
                    ['Akan datang', $stats['upcoming'], 'ki-calendar', 'primary'],
                    ['Total media', $stats['media'], 'ki-picture', 'info'],
                ] as [$label, $value, $icon, $color])
                    <div class="col-6 col-xl-3">
                        <div class="card card-flush h-100">
                            <div class="card-body d-flex align-items-center gap-4 py-5">
                                <span class="symbol symbol-45px">
                                    <span class="symbol-label bg-light-{{ $color }}">
                                        <i class="ki-outline {{ $icon }} fs-2 text-{{ $color }}"></i>
                                    </span>
                                </span>
                                <div>
                                    <div class="fs-2 fw-bold text-gray-900">{{ number_format($value, 0, ',', '.') }}</div>
                                    <div class="fs-7 text-muted">{{ $label }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- ---------------------------------------------------- Tabel --}}
            <div class="card card-flush">
                <div class="card-header py-6 flex-wrap gap-3">
                    <div class="card-title">
                        <div class="d-flex align-items-center position-relative">
                            <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                            <input type="text" id="event-search" class="form-control form-control-solid w-250px ps-13"
                                placeholder="Cari acara, klien, slug" />
                        </div>
                    </div>

                    <div class="card-toolbar gap-2">
                        <select id="event-status" class="form-select form-select-solid w-150px">
                            <option value="">Semua status</option>
                            @foreach (\App\Models\Event::STATUSES as $status)
                                <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>

                        <a href="{{ route('events.create') }}" class="btn btn-sm btn-primary">
                            <i class="ki-outline ki-plus fs-2"></i>Acara Baru
                        </a>
                    </div>
                </div>

                <div class="card-body pt-0">
                    <table class="table align-middle table-row-dashed fs-6 gy-5" id="event-table">
                        <thead>
                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                <th class="min-w-250px">Acara</th>
                                <th class="min-w-150px">Jadwal</th>
                                <th class="min-w-125px">Pemakaian</th>
                                <th class="min-w-100px">Status</th>
                                <th class="text-end min-w-150px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 fw-semibold"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        $(function () {
            var table = $('#event-table').DataTable({
                processing: true,
                serverSide: true,
                searching: true,
                dom: 'rt<"d-flex justify-content-between align-items-center flex-wrap gap-2 pt-5"lip>',
                pageLength: 10,
                order: [],
                language: { url: "{{ asset('assets/plugins/custom/datatables/id.json') }}" },
                ajax: {
                    url: "{{ route('events.data') }}",
                    data: function (params) {
                        params.status = $('#event-status').val();
                    }
                },
                columns: [
                    { data: 'event', name: 'title' },
                    { data: 'schedule', name: 'event_date' },
                    { data: 'usage', name: 'usage', orderable: false, searchable: false },
                    { data: 'status', name: 'status' },
                    { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end' }
                ]
            });

            $('#event-search').on('keyup', function () { table.search(this.value).draw(); });
            $('#event-status').on('change', function () { table.draw(); });
        });
    </script>
@endpush
