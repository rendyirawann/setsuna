@extends('portal.layout')

@section('title', 'Kamera ditutup')

@section('content')

    <section class="portal-hero">
        <div class="portal-hero__bg"
            style="background-image:radial-gradient(circle at 50% 35%,#2e2418,transparent 62%)"></div>

        <div class="wrap portal-hero__inner" style="max-width:620px">
            <span class="date">{{ $event->couple }}</span>

            <h1 style="font-size:clamp(2.2rem,7vw,4rem);margin-top:.8rem">Kamera<br />sedang tutup</h1>

            @include('partials.enso')

            <p class="muted">{{ $reason }}</p>

            <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:34px">
                @if ($event->isGalleryVisible())
                    <a href="{{ $event->galleryUrl() }}" class="btn btn--gold">Lihat album bersama</a>
                @endif

                @if ($guest)
                    <a href="{{ route('portal.roll', $event->slug) }}" class="btn btn--ghost">Lihat rollku</a>
                @endif

                <a href="{{ $event->portalUrl() }}" class="btn btn--ghost">Halaman acara</a>
            </div>
        </div>
    </section>

@endsection
