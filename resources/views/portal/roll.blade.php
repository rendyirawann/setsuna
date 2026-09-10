@extends('portal.layout')

@section('title', 'Rollku')

@section('content')

    <section class="section" style="padding-top:clamp(40px,6vw,70px)">
        <div class="wrap">

            <div class="section__head center">
                <span class="kana">じぶんの · 自分の一巻</span>
                <span class="eyebrow">Roll pribadi</span>
                <h2>{{ $guest ? 'Jepretan ' . $guest->name : 'Belum ada rollmu' }}</h2>

                @if ($guest)
                    <p class="muted">
                        Sisa jatah: {{ $guest->remaining('photo') }} foto ·
                        {{ $guest->remaining('video') }} video ·
                        {{ $guest->remaining('boomerang') }} boomerang
                    </p>
                @else
                    <p class="muted">
                        Perangkat ini belum pernah memotret di acara {{ $event->couple }}.
                        Buka kamera dulu lewat QR di venue.
                    </p>
                @endif
            </div>

            @if ($media->count())
                <div class="gallery-grid">
                    @foreach ($media as $item)
                        @include('portal._tile', ['item' => $item, 'event' => $event])
                    @endforeach
                </div>
            @else
                <div class="empty">
                    <p style="margin:0 0 18px">Belum ada jepretan tersimpan.</p>
                    @if ($event->isCaptureOpen())
                        <a href="{{ route('portal.camera', $event->slug) }}" class="btn btn--gold">Buka kamera</a>
                    @endif
                </div>
            @endif

            @if ($guest && $event->isCaptureOpen())
                <div class="center" style="margin-top:40px">
                    <a href="{{ route('portal.camera', $event->slug) }}" class="btn btn--gold">Lanjut memotret</a>
                </div>
            @endif

            @if (! $galleryVisible)
                <p class="small muted center" style="margin-top:30px">
                    Album bersama masih terkunci. {{ $event->galleryHiddenReason() }}
                </p>
            @endif
        </div>
    </section>

    @include('portal._lightbox')

@endsection
