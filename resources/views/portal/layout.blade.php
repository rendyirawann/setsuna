<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta name="theme-color" content="#0b0a09" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <title>@yield('title', $event->title)</title>
    <meta name="description" content="Album bersama {{ $event->couple }}." />

    <link rel="icon" href="{{ asset('assets/media/branding/favicon.ico') }}" sizes="any" />
    <link rel="apple-touch-icon" href="{{ asset('assets/media/branding/apple-touch-icon.png') }}" />

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;1,300;1,400&family=Shippori+Mincho:wght@400;500;600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" />
    <link rel="stylesheet" href="{{ asset('assets/css/setsuna.css') }}" />

    @stack('styles')
</head>

<body>

    {{-- Tirai pembuka, 1,2 detik. Ditaruh paling atas agar tampil
         sebelum apa pun sempat terlihat. --}}
    <div class="curtain" id="curtain" aria-hidden="true">
        <div>
            <svg class="curtain__enso" viewBox="0 0 64 64" fill="none" aria-hidden="true">
                <defs>
                    <linearGradient id="curtain-gold" x1="0.1" y1="0" x2="0.9" y2="1">
                        <stop offset="0" stop-color="#F4DFC0" />
                        <stop offset="1" stop-color="#C7985E" />
                    </linearGradient>
                </defs>
                <path stroke="url(#curtain-gold)" stroke-width="2.4" stroke-linecap="round"
                    d="M45.5 12.5a24 24 0 1 0 8.2 12.4" />
            </svg>

            <span class="curtain__kana">せつな</span>
            <p class="curtain__name">{{ $event->couple }}</p>
        </div>
    </div>

    <header class="site-header">
        <div class="wrap site-header__bar">
            <a href="{{ $event->portalUrl() }}" class="brand">
                <img src="{{ asset('assets/media/branding/logo-mark.svg') }}" alt="" width="30" height="30" />
                <span style="font-size:1.2rem">{{ $event->couple }}</span>
            </a>

            <nav class="site-nav">
                <a href="{{ $event->portalUrl() }}">Acara</a>
                @if ($event->isGalleryVisible())
                    <a href="{{ $event->galleryUrl() }}">Album</a>
                @endif
                @if ($guest ?? false)
                    <a href="{{ route('portal.roll', $event->slug) }}">Rollku</a>
                @endif
            </nav>

            @if ($event->isCaptureOpen())
                <a href="{{ route('portal.camera', $event->slug) }}" class="btn btn--gold btn--sm">Buka kamera</a>
            @elseif ($event->isGalleryVisible())
                <a href="{{ $event->galleryUrl() }}" class="btn btn--ghost btn--sm">Lihat album</a>
            @endif
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="wrap">
            <div class="site-footer__base" style="border:0;padding-top:0">
                <span>{{ $event->title }}{{ $event->event_date ? ' · ' . $event->event_date->translatedFormat('d F Y') : '' }}</span>
                <span>
                    Album dibuat dengan
                    <a href="{{ route('home') }}" style="display:inline;color:var(--gold)">{{ $brand['name'] }}</a>
                </span>
            </div>
        </div>
    </footer>

    <script>
        (function () {
            var items = document.querySelectorAll('.reveal');

            if (!('IntersectionObserver' in window) || !items.length) {
                items.forEach(function (el) { el.classList.add('is-in'); });
                return;
            }

            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-in');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1, rootMargin: '0px 0px -40px' });

            items.forEach(function (el, index) {
                el.style.transitionDelay = Math.min(index % 8, 6) * 55 + 'ms';
                observer.observe(el);
            });
        })();
    </script>

    <script>
        // Tirai pembuka: 1,2 detik, lalu dilepas dari DOM supaya tidak
        // menyisakan lapisan yang menghalangi ketukan.
        (function () {
            var curtain = document.getElementById('curtain');

            if (!curtain) {
                return;
            }

            window.setTimeout(function () {
                curtain.hidden = true;
                curtain.remove();
            }, 1600);
        })();

        window.SETSUNA_PORTAL = @json(['feed' => route('portal.feed', $event->slug), 'now' => now()->utc()->format('Y-m-d\TH:i:s.v\Z')]);
    </script>
    <script src="{{ asset('assets/js/setsuna-portal.js') }}"></script>

    @stack('scripts')

    {{-- Kredit pembuat: CTRL + SHIFT + ALT + R --}}
    @include('partials._rt')
</body>

</html>
