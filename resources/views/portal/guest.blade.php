@extends('portal.layout')

@section('title', 'Jepretan ' . $album->name)

@section('content')

    <section class="section" style="padding-top:clamp(40px,6vw,70px)">
        <div class="wrap">

            <div class="section__head center">
                <span class="eyebrow">Album satu tamu</span>
                <h2>{{ $album->name }}</h2>
                <p class="muted">{{ $media->count() }} momen di acara {{ $event->couple }}.</p>
                <a href="{{ $event->galleryUrl() }}" class="btn btn--ghost btn--sm" style="margin-top:10px">
                    ← Kembali ke album bersama
                </a>
            </div>

            @if ($media->count())
                <div class="gallery-grid">
                    @foreach ($media as $item)
                        @include('portal._tile', ['item' => $item, 'event' => $event])
                    @endforeach
                </div>
            @else
                <div class="empty">
                    <p style="margin:0">Tamu ini belum mengunggah apa pun.</p>
                </div>
            @endif
        </div>
    </section>

    @include('portal._lightbox')

@endsection
