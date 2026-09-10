{{-- Judul halaman + breadcrumb untuk modul acara.
     Pakai: @include('backend.events._header', ['heading' => '...', 'crumbs' => ['Acara', 'Daftar']]) --}}
<div class="app-toolbar py-3 py-lg-0">
    <div class="app-container container-xxl d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center me-3">
            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                {{ $heading }}
            </h1>

            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                <li class="breadcrumb-item text-gray-700 fw-bold lh-1">
                    <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">
                        <i class="ki-outline ki-home text-gray-700 fs-6"></i>
                    </a>
                </li>
                @foreach ($crumbs ?? [] as $crumb)
                    <li class="breadcrumb-item"><i class="ki-outline ki-right fs-5 text-gray-700 mx-n1"></i></li>
                    <li class="breadcrumb-item {{ $loop->last ? 'text-gray-900' : 'text-muted' }}">{{ $crumb }}</li>
                @endforeach
            </ul>
        </div>

        @isset($actions)
            <div class="d-flex align-items-center gap-2 py-2">{!! $actions !!}</div>
        @endisset
    </div>
</div>
