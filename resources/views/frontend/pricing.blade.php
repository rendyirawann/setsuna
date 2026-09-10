@extends('frontend.layout')

@section('title', 'Harga paket')
@section('meta_description', 'Sekali bayar per acara. Pilih paket sesuai jumlah tamu, dari acara intim sampai wisuda kampus.')

@section('content')

    <section class="section" style="padding-top:clamp(50px,8vw,90px)">
        <div class="wrap">
            <div class="section__head center">
                <span class="kana">りょうきん · 料金</span>
                <span class="eyebrow">Harga</span>
                <h2>Sekali bayar,<br />untuk satu acara</h2>
                <p class="muted">
                    Tidak ada langganan bulanan dan tidak ada biaya per foto. Satu paket sudah
                    termasuk portal acara sendiri, papan QR siap cetak, dan album online.
                </p>
            </div>

            <div class="grid grid--3">
                @foreach ($plans as $plan)
                    @include('frontend._plan-card', ['plan' => $plan])
                @endforeach
            </div>
        </div>
    </section>

    <section class="section section--panel">
        <div class="wrap">
            <div class="section__head center">
                <span class="eyebrow">Termasuk di semua paket</span>
                <h2>Yang selalu kamu dapat</h2>
            </div>

            <div class="grid grid--4">
                <div class="card reveal">
                    <div class="icon-dot">@include("partials.line-icon", ["name" => "link"])</div>
                    <h3 style="font-size:1.15rem">Portal sendiri</h3>
                    <p>Alamat khusus acaramu, mis. /pernikahan-emma, tempat semua hasil berkumpul.</p>
                </div>
                <div class="card reveal">
                    <div class="icon-dot">@include("partials.line-icon", ["name" => "camera"])</div>
                    <h3 style="font-size:1.15rem">Kamera tanpa aplikasi</h3>
                    <p>Scan QR, kamera langsung terbuka di browser tamu. Tidak ada yang perlu diunduh.</p>
                </div>
                <div class="card reveal">
                    <div class="icon-dot">@include("partials.line-icon", ["name" => "printer"])</div>
                    <h3 style="font-size:1.15rem">Papan QR siap cetak</h3>
                    <p>Desain papan lengkap dengan instruksi, tinggal cetak dan pajang di venue.</p>
                </div>
                <div class="card reveal">
                    <div class="icon-dot">@include("partials.line-icon", ["name" => "download"])</div>
                    <h3 style="font-size:1.15rem">Unduh semuanya</h3>
                    <p>Semua foto dan video bisa diunduh sekaligus dalam satu berkas zip.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="wrap center">
            @include('partials.enso')
            <h2>Masih ragu paket mana?</h2>
            <p class="muted" style="max-width:32rem;margin-inline:auto">
                Patokan sederhana: hitung perkiraan tamu yang membawa ponsel. Kalau di bawah
                seratus, Intimate sudah cukup. Resepsi gedung biasanya pas di Signature.
            </p>
            <a href="{{ route('faq') }}" class="btn btn--ghost" style="margin-top:24px">Lihat pertanyaan umum</a>
        </div>
    </section>

@endsection
