{{-- Penampil layar penuh untuk satu momen.

     Gaya dan perilakunya ada di setsuna.css dan setsuna-portal.js.
     Jangan kembalikan style="display:flex" ke elemen ini: atribut
     [hidden] kalah oleh inline style, dan akibatnya overlay tidak
     pernah benar-benar tertutup. --}}

<div class="lightbox" id="lightbox" hidden>
    <div class="lightbox__bar">
        <span class="lightbox__by" id="lightbox-by"></span>

        <div class="lightbox__tools">
            <a id="lightbox-download" class="btn btn--ghost btn--sm" href="#" download>Unduh</a>
            <button type="button" id="lightbox-close" class="btn btn--ghost btn--sm">Tutup ✕</button>
        </div>
    </div>

    <div class="lightbox__stage">
        <img id="lightbox-image" alt="" hidden />
        <video id="lightbox-video" playsinline controls hidden></video>
    </div>
</div>
