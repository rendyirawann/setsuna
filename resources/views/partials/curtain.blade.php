{{-- Tirai pembuka, 1,2 detik.

     Ensō menggambar dirinya, judulnya muncul, lalu tirainya terangkat.
     Diletakkan paling atas di <body> supaya tampil sebelum apa pun
     sempat terlihat.

     Pakai: @include('partials.curtain', ['title' => 'Natasha & Xavier'])
--}}
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
        <p class="curtain__name">{{ $title }}</p>
    </div>
</div>

<script>
    // Tirai dilepas dari DOM setelah animasinya selesai supaya tidak
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
</script>
