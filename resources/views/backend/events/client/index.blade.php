@extends('backend.layout.app')
@section('title', 'Klien')

@section('content')

    @include('backend.events._header', [
        'heading' => 'Klien',
        'crumbs' => ['Manajemen Acara', 'Klien'],
    ])

    <div class="app-content flex-column-fluid">
        <div class="app-container container-xxl">
            <div class="card card-flush">
                <div class="card-header py-6">
                    <div class="card-title">
                        <div class="d-flex align-items-center position-relative">
                            <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                            <input type="text" id="client-search" class="form-control form-control-solid w-250px ps-13"
                                placeholder="Cari klien" />
                        </div>
                    </div>
                    <div class="card-toolbar">
                        <a href="{{ route('clients.create') }}" class="btn btn-sm btn-primary">
                            <i class="ki-outline ki-plus fs-2"></i>Klien Baru
                        </a>
                    </div>
                </div>

                <div class="card-body pt-0">
                    <table class="table align-middle table-row-dashed fs-6 gy-5" id="client-table">
                        <thead>
                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                <th class="min-w-250px">Klien</th>
                                <th class="min-w-200px">Kontak</th>
                                <th class="min-w-100px">Kota</th>
                                <th class="text-end min-w-125px">Aksi</th>
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
            var table = $('#client-table').DataTable({
                processing: true,
                serverSide: true,
                dom: 'rt<"d-flex justify-content-between align-items-center flex-wrap gap-2 pt-5"lip>',
                pageLength: 10,
                order: [],
                ajax: "{{ route('clients.data') }}",
                columns: [
                    { data: 'client', name: 'name' },
                    { data: 'contact', name: 'email' },
                    { data: 'city', name: 'city' },
                    { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end' }
                ]
            });

            $('#client-search').on('keyup', function () { table.search(this.value).draw(); });
        });
    </script>
@endpush
