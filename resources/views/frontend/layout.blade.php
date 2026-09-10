<!DOCTYPE html>
<html lang="id">

<head>
    @include('partials.meta')

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,300;1,400&family=Shippori+Mincho:wght@400;500;600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" />

    <link rel="stylesheet" href="{{ asset('assets/css/setsuna.css') }}" />

    @stack('styles')
</head>

<body>

    <header class="site-header">
        <div class="wrap site-header__bar">
            <a href="{{ route('home') }}" class="brand">
                <img src="{{ asset('assets/media/branding/logo-mark.svg') }}" alt="" width="34" height="34" />
                <span style="display:flex;flex-direction:column;line-height:1">
                    <b style="font-family:var(--sans);font-size:1.02rem;font-weight:500;letter-spacing:.34em">{{ $brand['name'] }}</b>
                    <small class="jp" style="font-size:.62rem;letter-spacing:.42em;color:var(--gold);opacity:.75">せつな</small>
                </span>
            </a>

            <nav class="site-nav">
                <a href="{{ route('home') }}#cara-kerja">Cara Kerja</a>
                <a href="{{ route('home') }}#acara">Jenis Acara</a>
                <a href="{{ route('pricing') }}">Harga</a>
                <a href="{{ route('faq') }}">FAQ</a>
            </nav>

            <a href="{{ route('pricing') }}" class="btn btn--gold btn--sm">Pesan Sekarang</a>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="wrap">
            <div class="site-footer__grid">
                <div>
                    <a href="{{ route('home') }}" class="brand" style="margin-bottom:14px">
                        <img src="{{ asset('assets/media/branding/logo-mark.svg') }}" alt="" width="30" height="30" />
                        <span>{{ $brand['name'] }}</span>
                    </a>
                    <p class="small" style="max-width:26rem">
                        <b class="jp gold">刹那</b> — sekejap yang tersimpan. Kamera sekali pakai
                        berbasis QR untuk acara yang butuh banyak mata: pernikahan, wisuda, prom,
                        sampai gathering kantor. Satu scan, satu roll, satu album bersama.
                    </p>
                    <p class="jp small" style="color:var(--muted-2);margin-top:14px">
                        一期一会 — <span style="font-family:var(--sans);letter-spacing:0">satu pertemuan, sekali seumur hidup.</span>
                    </p>
                </div>

                <div>
                    <h4>Jelajahi</h4>
                    <a href="{{ route('home') }}#cara-kerja">Cara kerja</a>
                    <a href="{{ route('home') }}#acara">Jenis acara</a>
                    <a href="{{ route('pricing') }}">Harga paket</a>
                    <a href="{{ route('faq') }}">Pertanyaan umum</a>
                </div>

                <div>
                    <h4>Bantuan</h4>
                    <a href="{{ route('faq') }}">Panduan hari-H</a>
                    <a href="{{ route('login') }}">Masuk admin</a>
                </div>
            </div>

            <div class="site-footer__base">
                <span>&copy; {{ date('Y') }} {{ $brand['name'] }}. Semua momen tersimpan rapi.</span>

                @php
                    $owner = $appSettings['owner_name'] ?? 'Rendy Irawan';
                    $github = $appSettings['owner_github'] ?? null;
                    $linkedin = $appSettings['owner_linkedin'] ?? null;
                @endphp

                <span class="footer-credit">
                    Dibuat oleh <b>{{ $owner }}</b>

                    @if ($github)
                        <a href="{{ $github }}" target="_blank" rel="noopener noreferrer" aria-label="GitHub {{ $owner }}">
                            @include('partials.line-icon', ['name' => 'github'])
                            <span>GitHub</span>
                        </a>
                    @endif

                    @if ($linkedin)
                        <a href="{{ $linkedin }}" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn {{ $owner }}">
                            @include('partials.line-icon', ['name' => 'linkedin'])
                            <span>LinkedIn</span>
                        </a>
                    @endif
                </span>
            </div>
        </div>
    </footer>

    <script>
        // Animasi masuk yang ringan: cukup satu observer untuk semua elemen.
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
            }, { threshold: 0.12, rootMargin: '0px 0px -60px' });

            items.forEach(function (el, index) {
                el.style.transitionDelay = Math.min(index % 6, 5) * 70 + 'ms';
                observer.observe(el);
            });
        })();
    </script>

    @stack('scripts')

    {{-- Kredit pembuat: CTRL + SHIFT + ALT + R --}}
    @include('partials._rt')
</body>

</html>
