<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover, user-scalable=no" />
    <meta name="theme-color" content="#0b0a09" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <title>Kamera · {{ $event->title }}</title>

    <link rel="icon" href="{{ asset('assets/media/branding/favicon.ico') }}" sizes="any" />
    <link rel="apple-touch-icon" href="{{ asset('assets/media/branding/apple-touch-icon.png') }}" />

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500&family=Shippori+Mincho:wght@400;500&family=Inter:wght@400;500;600&display=swap" />
    <link rel="stylesheet" href="{{ asset('assets/css/setsuna-camera.css') }}" />
</head>

<body class="cam-body">

    <div class="cam">

        {{-- ------------------------------------------------ Viewfinder --}}
        <div class="cam__stage">
            <video id="cam-video" playsinline autoplay muted
                style="filter: {{ $preset['css'] }}"></video>
            <div class="cam__grain"></div>
            <div class="cam__vignette"></div>
            <div class="cam__flash" id="cam-flash"></div>
        </div>

        <canvas id="cam-canvas" hidden></canvas>

        {{-- ---------------------------------------------------- Bar atas --}}
        <div class="cam__top">
            <div class="cam__event">
                <img src="{{ asset('assets/media/branding/logo-mark.svg') }}" alt="" />
                <span>{{ $event->couple }}</span>
            </div>

            <div class="cam__tools">
                <button type="button" class="cam-icon" id="cam-torch" hidden aria-label="Lampu kilat">⚡</button>
                <a class="cam-icon" href="{{ $event->portalUrl() }}" aria-label="Halaman acara">✕</a>
            </div>
        </div>

        <div class="cam__uploading" id="cam-uploading">Mengunggah…</div>

        {{-- ------------------------------------------------- Penghitung --}}
        <div class="cam__counter">
            <span class="n" id="cam-count-prev">19</span>
            <span class="n n--now" id="cam-count-now">18</span>
            <span class="n" id="cam-count-next">17</span>
            <span class="label" id="cam-count-label">foto tersisa</span>
        </div>

        {{-- ---------------------------------------------------- Bar bawah --}}
        <div class="cam__bottom">
            <div class="cam__modes">
                <button type="button" class="cam__mode is-on" data-mode="photo">
                    Foto<small>{{ $event->photo_quota }} tersisa</small>
                </button>
                @if ($event->video_quota > 0)
                    <button type="button" class="cam__mode" data-mode="video">
                        Video {{ $event->video_duration }}s<small>{{ $event->video_quota }} tersisa</small>
                    </button>
                @endif
                @if ($event->boomerang_quota > 0)
                    <button type="button" class="cam__mode" data-mode="boomerang">
                        Boomerang<small>{{ $event->boomerang_quota }} tersisa</small>
                    </button>
                @endif
            </div>

            <div class="cam__actions">
                <button type="button" class="roll-thumb" id="cam-roll-btn" aria-label="Lihat rollmu">
                    <span id="cam-roll-thumb">🎞️</span>
                    <b id="cam-roll-count" hidden>0</b>
                </button>

                <button type="button" class="shutter" id="cam-shutter" aria-label="Ambil">
                    <span class="shutter__core"></span>
                    <svg class="shutter__ring" id="cam-ring" viewBox="0 0 88 88">
                        <circle cx="44" cy="44" r="41"></circle>
                    </svg>
                </button>

                <button type="button" class="cam-icon" id="cam-flip" aria-label="Balik kamera"
                    style="justify-self:center;width:52px;height:52px;font-size:1.2rem">⟳</button>
            </div>
        </div>

        <div class="cam__toast" id="cam-toast"></div>

        {{-- ------------------------------------------- Gerbang nama tamu --}}
        <div class="cam__sheet" id="cam-gate" hidden>
            <div class="cam__sheet-mid">
                <span class="eyebrow" style="font-family:'Shippori Mincho',serif;letter-spacing:.4em;color:#c9a06a">せつな</span>
                <span class="eyebrow">{{ $event->type_label }} · {{ $event->couple }}</span>
                <h2>Kamu jadi fotografer<br />malam ini</h2>
                <p>
                    {{ $event->welcome_message ?: 'Kamu dapat kamera sekali pakai. Pakai baik-baik, jatahnya terbatas.' }}
                </p>

                <div class="cam__quota-list">
                    <div><span>Foto</span><b>{{ $event->photo_quota }}</b></div>
                    @if ($event->video_quota > 0)
                        <div><span>Video {{ $event->video_duration }} detik</span><b>{{ $event->video_quota }}</b></div>
                    @endif
                    @if ($event->boomerang_quota > 0)
                        <div><span>Boomerang</span><b>{{ $event->boomerang_quota }}</b></div>
                    @endif
                </div>

                <form id="cam-gate-form">
                    <div class="cam-field">
                        <input type="text" id="cam-gate-name" maxlength="40" autocomplete="name"
                            placeholder="Namamu, biar hasilnya bertanda" />
                    </div>
                    <button type="submit" class="cam-btn" id="cam-gate-btn">Mulai memotret</button>
                </form>

                <p style="font-size:.78rem;margin-top:16px;text-align:center">
                    Hasil jepretanmu masuk ke album {{ $event->couple }}.
                </p>
            </div>
        </div>

        {{-- ------------------------------------------------- Roll pribadi --}}
        <div class="cam__sheet" id="cam-roll-sheet" hidden>
            <button type="button" class="cam-icon sheet-close" id="cam-roll-close" aria-label="Tutup">✕</button>
            <span class="eyebrow" style="font-family:'Shippori Mincho',serif;letter-spacing:.4em;color:#c9a06a">じぶん · 自分の一巻</span>
            <span class="eyebrow">Rollmu</span>
            <h2>Hasil jepretanmu</h2>
            <p>Semua ini akan muncul di album bersama {{ $event->couple }}.</p>
            <div class="roll-grid" id="cam-roll-grid"></div>
        </div>

        {{-- ---------------------------------------------------- Roll habis --}}
        <div class="cam__sheet" id="cam-done" hidden>
            <div class="cam__sheet-mid" style="text-align:center">
                <span class="eyebrow" style="font-family:'Shippori Mincho',serif;letter-spacing:.4em;color:#c9a06a">おわり · 終</span>
                <span class="eyebrow">Roll habis</span>
                <h2>Filmmu sudah<br />terpakai semua</h2>
                <p>
                    Terima kasih sudah ikut merekam {{ $event->couple }}. Hasilnya
                    {{ $event->isGalleryVisible() ? 'sudah bisa dilihat di album bersama.' : 'dibuka bersamaan setelah acara selesai.' }}
                </p>
                <div id="cam-done-quota"></div>
                <a class="cam-btn" href="{{ $event->isGalleryVisible() ? $event->galleryUrl() : route('portal.roll', $event->slug) }}">
                    {{ $event->isGalleryVisible() ? 'Lihat album bersama' : 'Lihat rollku' }}
                </a>
                <a class="cam-btn cam-btn--ghost" href="{{ $event->portalUrl() }}">Halaman acara</a>
            </div>
        </div>

        {{-- --------------------------------------------------- Galat izin --}}
        <div class="cam__sheet" id="cam-error" hidden>
            <div class="cam__sheet-mid" style="text-align:center">
                <span class="eyebrow">Kamera belum aktif</span>
                <h2>Izinkan akses<br />kamera dulu</h2>
                <p id="cam-error-text">Kami butuh izin kamera untuk mulai memotret.</p>
                <button type="button" class="cam-btn" id="cam-retry">Coba lagi</button>
                <a class="cam-btn cam-btn--ghost" href="{{ $event->portalUrl() }}">Kembali ke halaman acara</a>
            </div>
        </div>
    </div>

    <script>
        window.SETSUNA_CAMERA = @json($config + ['csrf' => csrf_token()]);
    </script>
    <script src="{{ asset('assets/js/setsuna-camera.js') }}"></script>
</body>

</html>
