{{-- Berkas DataTables, hanya untuk halaman yang benar-benar memakai tabel.

     Bundle-nya besar (sekitar 2,4 MB JS) dan sebelumnya dimuat di setiap
     halaman admin — termasuk form dan halaman detail yang tidak punya
     tabel sama sekali.

     Pakai: @include('backend.partials.datatables') di view yang memanggil
     .DataTable(). --}}

@push('stylesheets')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
@endpush

@push('scripts')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush
