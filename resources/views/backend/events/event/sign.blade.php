<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex, nofollow" />
    <title>Papan QR · {{ $event->title }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;1,300&family=Shippori+Mincho:wght@400;500&family=Inter:wght@300;400;500&display=swap" />

    <style>
        /* Papan siap cetak. Ukurannya A3 potret; cetak ke PDF lalu
           kirim ke percetakan, atau cetak A4 langsung dari browser. */
        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: #6b6b6b;
            font-family: 'Inter', system-ui, sans-serif;
            padding: 26px;
        }

        .toolbar {
            max-width: 297mm;
            margin: 0 auto 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .toolbar a, .toolbar button {
            border: 0;
            border-radius: 999px;
            padding: 10px 20px;
            font-size: 0.86rem;
            font-family: inherit;
            cursor: pointer;
            background: #f4dfc0;
            color: #241c14;
            text-decoration: none;
        }

        .toolbar .ghost { background: rgba(255, 255, 255, 0.16); color: #fff; }

        /* --- Papan --- */
        .board {
            width: 297mm;
            min-height: 420mm;
            margin: 0 auto;
            background: #12100e;
            color: #f7ebd9;
            padding: 34mm 26mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            position: relative;
            box-shadow: 0 30px 90px rgba(0, 0, 0, 0.5);
        }

        .board::before {
            content: '';
            position: absolute;
            inset: 10mm;
            border: 1px solid rgba(244, 223, 192, 0.22);
            pointer-events: none;
        }

        .kana {
            font-family: 'Shippori Mincho', serif;
            font-size: 13pt;
            letter-spacing: 0.5em;
            color: #c9a06a;
            margin-bottom: 6mm;
        }

        .board h1 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 46pt;
            font-weight: 400;
            line-height: 1.02;
            margin: 0 0 6mm;
        }

        .intro {
            font-size: 12pt;
            color: #b7a692;
            max-width: 78%;
            line-height: 1.6;
            margin: 0 0 10mm;
        }

        .qr {
            background: #f7ebd9;
            padding: 8mm;
            border-radius: 4px;
            width: 105mm;
            height: 105mm;
            display: grid;
            place-items: center;
            margin-bottom: 8mm;
        }

        .qr svg { width: 100%; height: 100%; display: block; }

        .url {
            font-family: 'Shippori Mincho', serif;
            font-size: 12pt;
            letter-spacing: 0.16em;
            color: #e6c79a;
            margin-bottom: 10mm;
            word-break: break-all;
        }

        .quota {
            display: flex;
            gap: 14mm;
            justify-content: center;
            border-top: 1px solid rgba(244, 223, 192, 0.2);
            border-bottom: 1px solid rgba(244, 223, 192, 0.2);
            padding: 7mm 0;
            margin-bottom: 9mm;
            width: 100%;
        }

        .quota b {
            display: block;
            font-family: 'Cormorant Garamond', serif;
            font-size: 30pt;
            color: #f4dfc0;
            font-weight: 400;
            line-height: 1;
        }

        .quota span {
            font-size: 8pt;
            letter-spacing: 0.24em;
            text-transform: uppercase;
            color: #8a7a68;
        }

        .steps {
            display: flex;
            gap: 10mm;
            justify-content: center;
            margin-bottom: auto;
        }

        .steps div { max-width: 55mm; }
        .steps i {
            display: block;
            font-family: 'Cormorant Garamond', serif;
            font-style: normal;
            font-size: 20pt;
            color: #c9a06a;
            margin-bottom: 2mm;
        }
        .steps p { font-size: 9.5pt; color: #a4927d; margin: 0; line-height: 1.5; }

        .foot {
            margin-top: 10mm;
            display: flex;
            align-items: center;
            gap: 4mm;
            font-size: 9pt;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: #6f6155;
        }

        .foot img { width: 9mm; height: 9mm; }

        @page { size: A3 portrait; margin: 0; }

        @media print {
            body { background: #fff; padding: 0; }
            .toolbar { display: none; }
            .board {
                box-shadow: none;
                width: 100%;
                min-height: 100vh;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>

<body>

    <div class="toolbar">
        <button onclick="window.print()">Cetak / simpan PDF</button>
        <a href="{{ route('events.qr.download', $event) }}" class="ghost">Unduh QR (SVG)</a>
        <a href="{{ $event->cameraUrl() }}" target="_blank" class="ghost">Uji buka kamera</a>
        <a href="{{ route('events.show', $event) }}" class="ghost">← Kembali ke admin</a>
    </div>

    <div class="board">
        <div class="kana">せつな</div>

        <h1>{{ $event->sign_message ? $event->couple : 'Kami tidak menyediakan fotografer' }}</h1>

        <p class="intro">
            {{ $event->sign_message ?: 'Malam ini kamu fotografernya. Scan QR di bawah, kameranya langsung terbuka — tidak perlu memasang aplikasi apa pun.' }}
        </p>

        <div class="qr">{!! $qr !!}</div>

        <div class="url">{{ str_replace(['https://', 'http://'], '', rtrim(config('app.url'), '/')) }}/{{ $event->slug }}</div>

        <div class="quota">
            <div>
                <b>{{ $event->photo_quota }}</b>
                <span>Foto</span>
            </div>
            @if ($event->video_quota > 0)
                <div>
                    <b>{{ $event->video_quota }}</b>
                    <span>Video {{ $event->video_duration }} detik</span>
                </div>
            @endif
            @if ($event->boomerang_quota > 0)
                <div>
                    <b>{{ $event->boomerang_quota }}</b>
                    <span>Boomerang</span>
                </div>
            @endif
        </div>

        <div class="steps">
            <div>
                <i>01</i>
                <p>Arahkan kamera ponsel ke kode QR di atas.</p>
            </div>
            <div>
                <i>02</i>
                <p>Tulis namamu sekali, supaya hasilnya bertanda nama.</p>
            </div>
            <div>
                <i>03</i>
                <p>Pakai jatahmu baik-baik — sekali terpakai tidak bisa diulang.</p>
            </div>
        </div>

        <div class="foot">
            <img src="{{ asset('assets/media/branding/logo-mark.svg') }}" alt="" />
            <span>{{ $brand['name'] }}</span>
        </div>
    </div>

</body>

</html>
