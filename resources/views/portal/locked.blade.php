@extends('portal.layout')

@section('title', 'Album belum dibuka')

@section('content')

    <section class="portal-hero">
        <div class="portal-hero__bg"
            style="background-image:radial-gradient(circle at 50% 30%,#332618,transparent 62%)"></div>

        <div class="wrap portal-hero__inner" style="max-width:640px">
            <span class="date">Album terkunci</span>

            <h1 style="font-size:clamp(2.2rem,7vw,4rem);margin-top:.8rem">
                Semuanya dibuka<br />bersamaan
            </h1>

            @include('partials.enso')

            <p class="muted">{{ $reason }}</p>

            <div class="stat-row" style="margin-top:30px">
                <div>
                    <b>{{ number_format($counts['guests'], 0, ',', '.') }}</b>
                    <span>Tamu memotret</span>
                </div>
                <div>
                    <b>{{ number_format($counts['total'], 0, ',', '.') }}</b>
                    <span>Momen menunggu</span>
                </div>
            </div>

            <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:36px">
                @if ($event->isCaptureOpen())
                    <a href="{{ route('portal.camera', $event->slug) }}" class="btn btn--gold">Lanjut memotret</a>
                @endif

                @if ($guest)
                    <a href="{{ route('portal.roll', $event->slug) }}" class="btn btn--ghost">Lihat rollku</a>
                @endif

                <a href="{{ $event->portalUrl() }}" class="btn btn--ghost">Halaman acara</a>
            </div>
        </div>
    </section>

@endsection
