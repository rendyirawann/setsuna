@extends('frontend.layout')

@section('title', 'Pesanan diterima')

@section('content')

    <section class="section" style="padding-top:clamp(60px,9vw,110px)">
        <div class="wrap" style="max-width:760px">

            <div class="center">
                @include('partials.enso')
                <span class="eyebrow">Pesanan diterima</span>
                <h2>Portal acaramu sudah disiapkan</h2>
                <p class="muted">
                    Nomor pesananmu <b class="gold">{{ $subscription->invoice_number }}</b>.
                    Kami mengirim instruksi pembayaran ke
                    <b>{{ $subscription->client->email }}</b>. Begitu pembayaran dikonfirmasi,
                    kamera tamu langsung aktif.
                </p>
            </div>

            <div class="card" style="margin-top:40px">
                <div class="grid grid--2" style="gap:20px">
                    <div>
                        <span class="eyebrow" style="margin-bottom:.4rem">Acara</span>
                        <b>{{ $subscription->event?->title }}</b>
                    </div>
                    <div>
                        <span class="eyebrow" style="margin-bottom:.4rem">Tanggal</span>
                        <b>{{ $subscription->event?->event_date?->translatedFormat('d F Y') ?? '-' }}</b>
                    </div>
                    <div>
                        <span class="eyebrow" style="margin-bottom:.4rem">Paket</span>
                        <b>{{ $subscription->plan?->name }}</b>
                    </div>
                    <div>
                        <span class="eyebrow" style="margin-bottom:.4rem">Total</span>
                        <b class="gold">{{ $subscription->total_label }}</b>
                    </div>
                    <div style="grid-column:1/-1;border-top:1px solid var(--line);padding-top:18px">
                        <span class="eyebrow" style="margin-bottom:.4rem">Alamat portal acaramu</span>
                        <b class="gold" style="word-break:break-all">
                            {{ rtrim(config('app.url'), '/') }}/{{ $subscription->event?->slug }}
                        </b>
                        <p class="small muted" style="margin-top:8px;margin-bottom:0">
                            Alamat ini menyala setelah pembayaran dikonfirmasi. Simpan baik-baik —
                            inilah tempat semua hasil jepretan tamumu berkumpul.
                        </p>
                    </div>
                </div>
            </div>

            <h3 style="margin-top:48px">Selanjutnya apa?</h3>

            <div class="steps">
                <div class="step">
                    <div class="step__n">01</div>
                    <div>
                        <h3>Selesaikan pembayaran</h3>
                        <p>Batas pembayaran {{ $subscription->due_at?->translatedFormat('d F Y') }}. Instruksinya ada di email.</p>
                    </div>
                </div>
                <div class="step">
                    <div class="step__n">02</div>
                    <div>
                        <h3>Kami aktifkan portal &amp; kirim papan QR</h3>
                        <p>Berkas papan QR siap cetak dikirim maksimal 1x24 jam setelah pembayaran masuk.</p>
                    </div>
                </div>
                <div class="step">
                    <div class="step__n">03</div>
                    <div>
                        <h3>Pajang papannya di hari-H</h3>
                        <p>Taruh di meja penerima tamu dan beberapa titik ramai. Sisanya tamu yang kerjakan.</p>
                    </div>
                </div>
            </div>

            <div class="center" style="margin-top:44px">
                <a href="{{ route('home') }}" class="btn btn--ghost">Kembali ke beranda</a>
            </div>
        </div>
    </section>

@endsection
