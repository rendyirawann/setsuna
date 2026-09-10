/* =========================================================================
   Hero yang mengikuti kursor.

   Foto latarnya bergeser sedikit melawan arah kursor sehingga terasa
   punya kedalaman. Geserannya kecil dan selalu kembali ke posisi diam
   saat kursor keluar.

   Tidak dijalankan sama sekali kalau perangkatnya tidak punya kursor
   sungguhan (ponsel, tablet) atau kalau pengguna meminta animasi
   dikurangi — di situ fotonya tetap di posisi bawaannya.
   ========================================================================= */

(function () {
    'use strict';

    var hero = document.querySelector('.hero');
    var image = hero && hero.querySelector('.hero__bg-img');

    if (!hero || !image) {
        return;
    }

    var finePointer = window.matchMedia('(hover: hover) and (pointer: fine)');
    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)');

    // Sejauh mana foto boleh bergeser. Harus lebih kecil dari ruang
    // lebih yang diberikan oleh scale di CSS, kalau tidak tepinya
    // terlihat kosong.
    var MAX_X = 18;
    var MAX_Y = 12;
    var SCALE = 1.08;

    var targetX = 0;
    var targetY = 0;
    var currentX = 0;
    var currentY = 0;
    var frame = null;
    var active = false;

    function apply() {
        image.style.transform = 'scale(' + SCALE + ') translate3d('
            + currentX.toFixed(2) + 'px, ' + currentY.toFixed(2) + 'px, 0)';
    }

    /** Mendekati posisi tujuan sedikit demi sedikit supaya tidak patah. */
    function tick() {
        currentX += (targetX - currentX) * 0.09;
        currentY += (targetY - currentY) * 0.09;

        apply();

        var settled = Math.abs(targetX - currentX) < 0.05 && Math.abs(targetY - currentY) < 0.05;

        if (settled) {
            currentX = targetX;
            currentY = targetY;
            apply();
            frame = null;

            return;
        }

        frame = requestAnimationFrame(tick);
    }

    function nudge() {
        if (!frame) {
            frame = requestAnimationFrame(tick);
        }
    }

    function onMove(event) {
        var box = hero.getBoundingClientRect();

        // -1 di tepi kiri/atas, +1 di tepi kanan/bawah.
        var nx = ((event.clientX - box.left) / box.width) * 2 - 1;
        var ny = ((event.clientY - box.top) / box.height) * 2 - 1;

        // Foto bergerak berlawanan arah kursor: itu yang memberi kesan
        // ia berada jauh di belakang teks.
        targetX = -Math.max(-1, Math.min(1, nx)) * MAX_X;
        targetY = -Math.max(-1, Math.min(1, ny)) * MAX_Y;

        nudge();
    }

    function onLeave() {
        targetX = 0;
        targetY = 0;
        nudge();
    }

    function enable() {
        if (active) {
            return;
        }

        active = true;
        hero.classList.add('hero--parallax');
        hero.addEventListener('mousemove', onMove);
        hero.addEventListener('mouseleave', onLeave);
        apply();
    }

    function disable() {
        if (!active) {
            return;
        }

        active = false;
        hero.classList.remove('hero--parallax');
        hero.removeEventListener('mousemove', onMove);
        hero.removeEventListener('mouseleave', onLeave);

        if (frame) {
            cancelAnimationFrame(frame);
            frame = null;
        }

        // Kembali ke posisi bawaan yang diatur CSS.
        targetX = targetY = currentX = currentY = 0;
        image.style.transform = '';
    }

    function sync() {
        if (finePointer.matches && !reduced.matches) {
            enable();
        } else {
            disable();
        }
    }

    // Kursor bisa muncul atau hilang di tengah jalan (tablet dengan
    // mouse, laptop layar sentuh), jadi keadaannya terus dipantau.
    if (finePointer.addEventListener) {
        finePointer.addEventListener('change', sync);
        reduced.addEventListener('change', sync);
    } else if (finePointer.addListener) {
        finePointer.addListener(sync);
        reduced.addListener(sync);
    }

    sync();
})();
