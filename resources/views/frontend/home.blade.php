@extends('frontend.layout')

@section('title', 'Kamera tamu untuk acaramu')
@section('meta_description', 'Satu QR memberi setiap tamu kamera sekali pakai. Untuk pernikahan, wisuda, prom, dan acara besar lainnya — semua hasilnya berkumpul di satu album.')

@section('content')

    {{-- ------------------------------------------------------------ Hero --}}
    {{-- Satu hero saja: foto jadi latar, teks SETSUNA di atasnya.
         Gambarnya sudah dipotong dari sisi yang membawa judul bawaan,
         jadi tidak ada dua judul yang bertabrakan. --}}
    <section class="hero">
        <div class="hero__bg" aria-hidden="true">
            @if (file_exists(public_path('assets/media/hero/hero.jpg')))
                <img src="{{ asset('assets/media/hero/hero.jpg') }}" alt="" class="hero__bg-img" />
            @elseif ($showcase->count())
                <div class="hero__collage">
                    @foreach ($showcase->take(12) as $item)
                        <figure><img src="{{ $item->previewUrl() }}" alt="" loading="lazy" /></figure>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="hero__veil" aria-hidden="true"></div>

        <div class="wrap hero__inner">
            <div>
                <span class="kana reveal">せつな</span>
                <span class="eyebrow reveal">Kamera tamu berbasis QR</span>

                <h1 class="reveal">
                    Lihat momen<br />
                    yang <span class="serif-accent">terlewat</span>
                </h1>

                <p class="lead reveal">
                    <b class="jp gold">刹那</b> — sekejap. Satu QR memberi setiap tamu kamera
                    sekali pakai di ponselnya sendiri. Satu preset film. Satu album yang
                    terungkap bersama, jadi kamu akhirnya melihat acaramu dari ratusan sudut
                    pandang, bukan cuma dari satu lensa.
                </p>

                <div class="hero__actions reveal">
                    <a href="{{ route('pricing') }}" class="btn btn--gold">Pilih paket</a>
                    <a href="#cara-kerja" class="btn btn--ghost">Lihat cara kerjanya</a>
                </div>

                <div class="hero__meta reveal">
                    <div>
                        <b>{{ $stats['captures'] > 0 ? number_format($stats['captures'], 0, ',', '.') : '18' }}</b>
                        <span>{{ $stats['captures'] > 0 ? 'Momen tersimpan' : 'Foto tiap tamu' }}</span>
                    </div>
                    <div>
                        <b>{{ $stats['guests'] > 0 ? number_format($stats['guests'], 0, ',', '.') : '2' }}</b>
                        <span>{{ $stats['guests'] > 0 ? 'Tamu memotret' : 'Video 15 detik' }}</span>
                    </div>
                    <div>
                        <b>0</b>
                        <span>Aplikasi diunduh</span>
                    </div>
                </div>
            </div>

            <div class="mosaic reveal" aria-hidden="true">
                @forelse ($showcase->take(3) as $item)
                    <figure>
                        <img src="{{ $item->previewUrl() }}" alt="" loading="lazy" />
                        @if ($item->guest)
                            <figcaption>{{ $item->guest->name }}</figcaption>
                        @endif
                    </figure>
                @empty
                    @foreach (['Ayu', 'Rizky', 'Sari'] as $name)
                        <figure>
                            <div style="width:100%;height:100%;background:linear-gradient(150deg,#221c16,#0f0d0b)"></div>
                            <figcaption>{{ $name }}</figcaption>
                        </figure>
                    @endforeach
                @endforelse
            </div>
        </div>
    </section>

    {{-- ------------------------------------------------------- Cara kerja --}}
    <section class="section section--panel" id="cara-kerja">
        <div class="wrap">
            <div class="section__head center">
                <span class="kana">てじゅん · 手順</span>
                <span class="eyebrow">Empat langkah</span>
                <h2>Sesederhana menempel<br />satu papan di pintu masuk</h2>
            </div>

            <div class="steps">
                <div class="step reveal">
                    <div class="step__n">01</div>
                    <div>
                        <h3>Pasang papan QR di venue</h3>
                        <p>
                            Kami siapkan papan siap cetak lengkap dengan QR acaramu. Taruh di meja
                            penerima tamu, di setiap meja bundar, atau di photo corner.
                        </p>
                    </div>
                </div>

                <div class="step reveal">
                    <div class="step__n">02</div>
                    <div>
                        <h3>Tamu scan, kamera langsung terbuka</h3>
                        <p>
                            Tidak ada aplikasi, tidak ada pendaftaran akun. Begitu QR dipindai,
                            layar ponsel tamu langsung berubah jadi kamera dengan preset film acaramu.
                        </p>
                    </div>
                </div>

                <div class="step reveal">
                    <div class="step__n">03</div>
                    <div>
                        <h3>Tiap tamu dapat jatah terbatas</h3>
                        <p>
                            18 foto, 2 video 15 detik, dan 1 boomerang. Justru karena terbatas,
                            tamu memotret dengan sadar — hasilnya jauh lebih bagus daripada seribu
                            foto asal jepret.
                        </p>
                    </div>
                </div>

                <div class="step reveal">
                    <div class="step__n">04</div>
                    <div>
                        <h3>Semua hasil terkumpul di akhir acara</h3>
                        <p>
                            Roll semua orang dibuka bersamaan di portal acaramu sendiri. Momen
                            yang tidak sempat kamu lihat langsung, akhirnya kelihatan.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ----------------------------------------------------- Jenis acara --}}
    <section class="section" id="acara">
        <div class="wrap">
            <div class="section__head center">
                <span class="kana">しゅるい · 種類</span>
                <span class="eyebrow">Bukan cuma pernikahan</span>
                <h2>Untuk setiap acara yang<br />butuh <span class="serif-accent">banyak mata</span></h2>
                <p class="muted">
                    Satu fotografer hanya bisa berdiri di satu tempat. Acara besar terjadi
                    di mana-mana sekaligus — dan tamumu sudah memegang kamera.
                </p>
            </div>

            <div class="grid grid--3">
                <div class="card reveal">
                    <div class="icon-dot">@include("partials.line-icon", ["name" => "rings"])</div>
                    <h3>Pernikahan</h3>
                    <p>
                        Tawa di meja keluarga, tangis ibu di pelaminan, dansa terakhir sebelum
                        pulang. Bagian yang tidak masuk daftar foto fotografer.
                    </p>
                </div>

                <div class="card reveal">
                    <div class="icon-dot">@include("partials.line-icon", ["name" => "cap"])</div>
                    <h3>Wisuda kampus</h3>
                    <p>
                        Ribuan wisudawan, ribuan keluarga, satu hari. Setiap toga dan setiap
                        pelukan tercatat tanpa perlu menambah kru dokumentasi.
                    </p>
                </div>

                <div class="card reveal">
                    <div class="icon-dot">@include("partials.line-icon", ["name" => "note"])</div>
                    <h3>Prom &amp; pensi sekolah</h3>
                    <p>
                        Panitia bisa fokus jalannya acara. Dokumentasinya datang sendiri dari
                        semua yang hadir, lengkap dengan nama pemotretnya.
                    </p>
                </div>

                <div class="card reveal">
                    <div class="icon-dot">@include("partials.line-icon", ["name" => "building"])</div>
                    <h3>Gathering kantor</h3>
                    <p>
                        Outing, town hall, anniversary perusahaan. Album internal langsung jadi
                        di hari yang sama, siap dipakai tim komunikasi.
                    </p>
                </div>

                <div class="card reveal">
                    <div class="icon-dot">@include("partials.line-icon", ["name" => "cake"])</div>
                    <h3>Ulang tahun &amp; syukuran</h3>
                    <p>
                        Acara yang terlalu santai untuk menyewa fotografer, tapi terlalu berharga
                        untuk dilupakan.
                    </p>
                </div>

                <div class="card reveal">
                    <div class="icon-dot">@include("partials.line-icon", ["name" => "lantern"])</div>
                    <h3>Festival &amp; acara publik</h3>
                    <p>
                        Sebar QR di beberapa titik, lalu pantau hasilnya masuk secara langsung dari
                        panel admin.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- --------------------------------------------------------- Preset --}}
    <section class="section section--panel">
        <div class="wrap">
            <div class="section__head">
                <span class="kana">ひかり · 光</span>
                <span class="eyebrow">Satu preset, satu suasana</span>
                <h2>Semua foto tampak dari<br />satu roll yang sama</h2>
                <p class="muted">
                    Setiap acara sudah otomatis memakai preset bawaan
                    <b class="gold">Natural + Enhance</b>. Kalau mau punya karakter lebih kuat,
                    pilih <b>satu</b> preset film di bawah — dipakai serentak oleh semua tamu,
                    jadi albumnya terasa utuh, bukan tempelan ratusan gaya kamera ponsel yang
                    berbeda.
                </p>
            </div>

            {{-- Preset bawaan, selalu didapat --}}
            @php $default = \App\Support\FilmPresets::default(); @endphp

            <div class="washi-card reveal" style="margin-bottom:26px">
                <div class="grid grid--2" style="gap:26px;align-items:center">
                    <div>
                        <span class="eyebrow" style="margin-bottom:.6rem">Selalu termasuk</span>
                        <div class="title-jp">
                            <h3 style="margin:0">{{ $default['name'] }}</h3>
                            <small>ひょうじゅん · 標準</small>
                        </div>
                        <p style="margin-top:.6rem">{{ $default['description'] }}</p>
                    </div>
                    <div style="height:130px;border-radius:12px;
                                background:linear-gradient(135deg,#6b5238,#2a2018 58%,#12100e);
                                filter:{{ $default['css'] }}"></div>
                </div>
            </div>

            <p class="small muted" style="margin-bottom:18px">
                Lalu pilih salah satu dari enam preset film ini:
            </p>

            <div class="grid grid--3">
                @foreach (\App\Support\FilmPresets::selectable() as $key => $preset)
                    <div class="card reveal">
                        <div style="height:120px;border-radius:12px;margin-bottom:18px;
                                    background:linear-gradient(135deg,#5c4630,#241c14 60%,#12100e);
                                    filter:{{ $preset['css'] }}"></div>
                        <h3 style="font-size:1.2rem">{{ $preset['name'] }}</h3>
                        <p>{{ $preset['description'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ---------------------------------------------------------- Harga --}}
    <section class="section" id="harga">
        <div class="wrap">
            <div class="section__head center">
                <span class="kana">りょうきん · 料金</span>
                <span class="eyebrow">Sekali bayar per acara</span>
                <h2>Paket langganan</h2>
                <p class="muted">
                    Tidak ada biaya bulanan. Satu paket berlaku untuk satu acara, lengkap dengan
                    portal dan album online sendiri.
                </p>
            </div>

            <div class="grid grid--3">
                @foreach ($plans as $plan)
                    @include('frontend._plan-card', ['plan' => $plan])
                @endforeach
            </div>
        </div>
    </section>

    {{-- -------------------------------------------------------- Showcase --}}
    @if ($showcase->count() >= 4)
        <section class="section section--panel">
            <div class="wrap">
                <div class="section__head center">
                    <span class="kana">おもいで · 思い出</span>
                    <span class="eyebrow">Dari album yang sudah jadi</span>
                    <h2>Begini rasanya melihat<br />acaramu dari mata tamu</h2>
                </div>

                <div class="gallery-grid">
                    @foreach ($showcase as $item)
                        <div class="tile reveal">
                            <img src="{{ $item->previewUrl() }}" alt="" loading="lazy" />
                            @if ($item->guest)
                                <span class="tile__by"><i></i>{{ $item->guest->name }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- -------------------------------------------------------------- CTA --}}
    <section class="section">
        <div class="wrap center">
            @include('partials.enso')
            <h2>Acaramu cuma sekali.<br />Rekam dari <span class="serif-accent">semua sudut</span>.</h2>
            <p class="muted" style="max-width:34rem;margin-inline:auto">
                Siapkan portal acaramu hari ini, cetak papan QR-nya, dan biarkan tamu
                melakukan sisanya.
            </p>
            <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap;margin-top:28px">
                <a href="{{ route('pricing') }}" class="btn btn--gold">Mulai pesan</a>
                <a href="{{ route('faq') }}" class="btn btn--ghost">Baca pertanyaan umum</a>
            </div>
        </div>
    </section>

@endsection
