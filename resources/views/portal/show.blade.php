@extends('portal.layout')

@section('title', $event->title)

@section('content')

    {{-- ------------------------------------------------------------ Hero --}}
    <section class="portal-hero">
        @if ($event->coverUrl())
            <div class="portal-hero__bg" style="background-image:url('{{ $event->coverUrl() }}')"></div>
        @else
            <div class="portal-hero__bg"
                style="background-image:radial-gradient(circle at 30% 20%,#3b2d1e,transparent 60%),radial-gradient(circle at 70% 70%,#2a2119,transparent 60%)"></div>
        @endif

        <div class="wrap portal-hero__inner">
            <span class="date">{{ $event->type_label }}</span>

            <h1 style="margin-top:.6rem">{{ $event->couple }}</h1>

            @include('partials.enso')

            <p class="date">
                {{ $event->event_date?->translatedFormat('d F Y') }}
                @if ($event->venue)
                    &nbsp;·&nbsp; {{ $event->venue }}
                @endif
            </p>

            @if ($event->welcome_message)
                <p style="max-width:34rem;margin:24px auto 0;font-family:var(--serif);font-size:1.3rem;font-style:italic;color:#d9cbb8">
                    “{{ $event->welcome_message }}”
                </p>
            @endif

            <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap;margin-top:36px">
                @if ($event->isCaptureOpen())
                    <a href="{{ route('portal.camera', $event->slug) }}" class="btn btn--gold">
                        📷 Buka kamera
                    </a>
                @endif

                @if ($galleryVisible)
                    <a href="{{ $event->galleryUrl() }}" class="btn btn--ghost">Lihat album bersama</a>
                @endif

                @if ($guest)
                    <a href="{{ route('portal.roll', $event->slug) }}" class="btn btn--ghost">Rollku</a>
                @endif
            </div>

            @unless ($event->isCaptureOpen())
                <p class="small muted" style="margin-top:20px">{{ $event->captureClosedReason() }}</p>
            @endunless
        </div>
    </section>

    {{-- ------------------------------------------------------- Statistik --}}
    <section class="section" style="padding-top:0">
        <div class="wrap">
            <div class="stat-row">
                <div>
                    <b>{{ number_format($counts['guests'], 0, ',', '.') }}</b>
                    <span>Tamu memotret</span>
                </div>
                <div>
                    <b>{{ number_format($counts['photo'], 0, ',', '.') }}</b>
                    <span>Foto</span>
                </div>
                <div>
                    <b>{{ number_format($counts['video'], 0, ',', '.') }}</b>
                    <span>Video</span>
                </div>
                <div>
                    <b>{{ number_format($counts['boomerang'], 0, ',', '.') }}</b>
                    <span>Boomerang</span>
                </div>
            </div>
        </div>
    </section>

    {{-- ----------------------------------------------------- Cara ikutan --}}
    <section class="section section--panel">
        <div class="wrap" style="max-width:860px">
            <div class="section__head center">
                <span class="kana">ごあんない · ご案内</span>
                <span class="eyebrow">Untuk tamu</span>
                <h2>Cara ikut memotret</h2>
            </div>

            <div class="steps">
                <div class="step">
                    <div class="step__n">01</div>
                    <div>
                        <h3>Buka kamera</h3>
                        <p>Scan QR di venue, atau ketuk tombol “Buka kamera” di halaman ini.</p>
                    </div>
                </div>
                <div class="step">
                    <div class="step__n">02</div>
                    <div>
                        <h3>Tulis namamu sekali</h3>
                        <p>Supaya hasil jepretanmu bertanda nama di album bersama.</p>
                    </div>
                </div>
                <div class="step">
                    <div class="step__n">03</div>
                    <div>
                        <h3>Pakai jatahmu baik-baik</h3>
                        <p>
                            {{ $event->photo_quota }} foto{{ $event->video_quota ? ', ' . $event->video_quota . ' video ' . $event->video_duration . ' detik' : '' }}{{ $event->boomerang_quota ? ', dan ' . $event->boomerang_quota . ' boomerang' : '' }}.
                            Sekali terpakai tidak bisa diulang — persis kamera film.
                        </p>
                    </div>
                </div>
            </div>

            @if ($event->isCaptureOpen())
                <div class="center" style="margin-top:40px">
                    <a href="{{ route('portal.camera', $event->slug) }}" class="btn btn--gold">Mulai sekarang</a>
                </div>
            @endif
        </div>
    </section>

    @if ($event->hashtag)
        <section class="section">
            <div class="wrap center">
                @include('partials.enso')
                <h2 class="serif-accent" style="font-style:italic">{{ $event->hashtag }}</h2>
            </div>
        </section>
    @endif

@endsection
