@extends('frontend.layout')

@section('title', 'Pertanyaan umum')
@section('meta_description', 'Jawaban singkat soal kamera tamu berbasis QR: cara pakai, kuota, privasi, dan hari-H.')

@section('content')

    <section class="section" style="padding-top:clamp(50px,8vw,90px)">
        <div class="wrap" style="max-width:820px">
            <div class="section__head">
                <span class="kana">しつもん · 質問</span>
                <span class="eyebrow">FAQ</span>
                <h2>Pertanyaan yang<br />paling sering masuk</h2>
            </div>

            @php
                $faqs = [
                    [
                        'Tamu perlu instal aplikasi?',
                        'Tidak. QR membuka halaman biasa di browser, dan halaman itu langsung berupa kamera. Tamu hanya perlu mengizinkan akses kamera sekali saat diminta browser.',
                    ],
                    [
                        'Kenapa jatahnya dibatasi?',
                        'Justru itu intinya. Dengan 18 foto, orang memotret dengan sadar dan memilih momen — persis seperti kamera film sekali pakai. Album akhirnya jadi enak dilihat, bukan tumpukan ribuan foto buram.',
                    ],
                    [
                        'Kalau jatah tamu habis padahal acaranya masih panjang?',
                        'Tuan rumah bisa menambah jatah lewat panel admin, baik untuk satu tamu tertentu maupun untuk semua tamu sekaligus.',
                    ],
                    [
                        'Kapan tamu bisa melihat hasil semua orang?',
                        'Kamu yang menentukan. Bisa langsung terbuka sejak awal acara, otomatis terbuka setelah acara selesai, atau kamu buka sendiri lewat panel admin saat momennya pas.',
                    ],
                    [
                        'Apakah foto bisa disaring dulu sebelum tayang?',
                        'Bisa. Aktifkan mode moderasi, maka setiap jepretan masuk sebagai draft dan baru muncul di album bersama setelah kamu setujui.',
                    ],
                    [
                        'Sinyal di gedung jelek, bagaimana?',
                        'Foto diunggah satu per satu begitu diambil, jadi tidak perlu koneksi kencang. Kalau unggahan gagal, halaman kamera mencoba lagi dan memberi tahu tamu.',
                    ],
                    [
                        'Siapa yang memiliki hasil fotonya?',
                        'Kamu, sebagai pemesan acara. Semua berkas bisa diunduh dalam satu zip, dan kamu bisa meminta penghapusan seluruh data acara kapan saja.',
                    ],
                    [
                        'Bisa dipakai selain pernikahan?',
                        'Bisa, dan memang dirancang begitu. Wisuda kampus, prom sekolah, gathering kantor, ulang tahun, sampai festival — mana pun yang butuh dokumentasi dari banyak sudut sekaligus.',
                    ],
                    [
                        'Berapa lama hasilnya tersimpan?',
                        'Tergantung paket: 60 hari, 90 hari, atau satu tahun. Sebelum masa simpan habis kami ingatkan supaya kamu sempat mengunduh semuanya.',
                    ],
                ];
            @endphp

            <div class="steps">
                @foreach ($faqs as [$question, $answer])
                    <div class="step reveal">
                        <div class="step__n">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</div>
                        <div>
                            <h3>{{ $question }}</h3>
                            <p>{{ $answer }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="center" style="margin-top:56px">
                <a href="{{ route('pricing') }}" class="btn btn--gold">Lihat paket</a>
            </div>
        </div>
    </section>

@endsection
