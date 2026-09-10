@extends('backend.layout.app')
@section('title', 'Pesanan')

@section('content')

    @include('backend.events._header', [
        'heading' => 'Pesanan &amp; Langganan',
        'crumbs' => ['Manajemen Acara', 'Pesanan'],
    ])

    <div class="app-content flex-column-fluid">
        <div class="app-container container-xxl">

            <div class="row g-5 mb-5">
                @foreach ([
                    ['Menunggu bayar', $stats['pending'], 'ki-time', 'warning'],
                    ['Lunas', $stats['paid'], 'ki-check-circle', 'success'],
                    ['Pendapatan', 'Rp ' . number_format($stats['revenue'], 0, ',', '.'), 'ki-dollar', 'primary'],
                ] as [$label, $value, $icon, $color])
                    <div class="col-md-4">
                        <div class="card card-flush h-100">
                            <div class="card-body d-flex align-items-center gap-4 py-5">
                                <span class="symbol symbol-45px">
                                    <span class="symbol-label bg-light-{{ $color }}">
                                        <i class="ki-outline {{ $icon }} fs-2 text-{{ $color }}"></i>
                                    </span>
                                </span>
                                <div>
                                    <div class="fs-3 fw-bold text-gray-900">{{ $value }}</div>
                                    <div class="fs-7 text-muted">{{ $label }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="card card-flush">
                <div class="card-header py-6 flex-wrap gap-3">
                    <div class="card-title">
                        <div class="d-flex align-items-center position-relative">
                            <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                            <input type="text" id="sub-search" class="form-control form-control-solid w-250px ps-13"
                                placeholder="Cari invoice / klien" />
                        </div>
                    </div>
                    <div class="card-toolbar gap-2">
                        <select id="sub-status" class="form-select form-select-solid w-175px">
                            <option value="">Semua status</option>
                            @foreach (\App\Models\Subscription::STATUSES as $status)
                                <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                        <a href="{{ route('subscriptions.create') }}" class="btn btn-sm btn-primary">
                            <i class="ki-outline ki-plus fs-2"></i>Pesanan Baru
                        </a>
                    </div>
                </div>

                <div class="card-body pt-0">
                    <table class="table align-middle table-row-dashed fs-6 gy-5" id="sub-table">
                        <thead>
                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                <th class="min-w-250px">Invoice</th>
                                <th class="min-w-125px">Paket</th>
                                <th class="min-w-125px">Total</th>
                                <th class="min-w-125px">Status</th>
                                <th class="text-end min-w-200px">Aksi</th>
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
            var table = $('#sub-table').DataTable({
                processing: true,
                serverSide: true,
                dom: 'rt<"d-flex justify-content-between align-items-center flex-wrap gap-2 pt-5"lip>',
                pageLength: 10,
                order: [],
                ajax: {
                    url: "{{ route('subscriptions.data') }}",
                    data: function (params) {
                        params.status = $('#sub-status').val();
                    }
                },
                columns: [
                    { data: 'invoice', name: 'invoice_number' },
                    { data: 'plan_name', name: 'plan_id', orderable: false },
                    { data: 'total_label', name: 'total' },
                    { data: 'status', name: 'status' },
                    { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end' }
                ]
            });

            $('#sub-search').on('keyup', function () { table.search(this.value).draw(); });
            $('#sub-status').on('change', function () { table.draw(); });
        });
    </script>
@endpush
