@extends('backend.layout.app')
@section('title', 'Paket Langganan')

@section('content')

    @include('backend.events._header', [
        'heading' => 'Paket Langganan',
        'crumbs' => ['Manajemen Acara', 'Paket'],
    ])

    <div class="app-content flex-column-fluid">
        <div class="app-container container-xxl">
            <div class="card card-flush">
                <div class="card-header py-6">
                    <div class="card-title">
                        <div class="d-flex align-items-center position-relative">
                            <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                            <input type="text" id="plan-search" class="form-control form-control-solid w-250px ps-13"
                                placeholder="Cari paket" />
                        </div>
                    </div>
                    <div class="card-toolbar">
                        <a href="{{ route('plans.create') }}" class="btn btn-sm btn-primary">
                            <i class="ki-outline ki-plus fs-2"></i>Paket Baru
                        </a>
                    </div>
                </div>

                <div class="card-body pt-0">
                    <table class="table align-middle table-row-dashed fs-6 gy-5" id="plan-table">
                        <thead>
                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                <th class="min-w-250px">Paket</th>
                                <th class="min-w-125px">Harga</th>
                                <th class="min-w-200px">Jatah per tamu</th>
                                <th class="min-w-100px">Status</th>
                                <th class="text-end min-w-100px">Aksi</th>
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
            var table = $('#plan-table').DataTable({
                processing: true,
                serverSide: true,
                dom: 'rt<"d-flex justify-content-between align-items-center flex-wrap gap-2 pt-5"lip>',
                pageLength: 10,
                order: [],
                ajax: "{{ route('plans.data') }}",
                columns: [
                    { data: 'plan', name: 'name' },
                    { data: 'price_label', name: 'price' },
                    { data: 'quota', name: 'photo_quota', orderable: false, searchable: false },
                    { data: 'status', name: 'is_active' },
                    { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end' }
                ]
            });

            $('#plan-search').on('keyup', function () { table.search(this.value).draw(); });
        });
    </script>
@endpush
