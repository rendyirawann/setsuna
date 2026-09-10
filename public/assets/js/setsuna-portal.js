/* =========================================================================
   Portal acara: penampil satu momen + album yang menyala sendiri.

   Dua hal yang dikerjakan berkas ini:
   1. Membuka satu jepretan besar (foto, video, boomerang).
   2. Menarik jepretan baru secara berkala, jadi album dan roll terisi
      sendiri begitu ada tamu yang memotret — tanpa menunggu jatah
      siapa pun habis dan tanpa perlu muat ulang.
   ========================================================================= */

(function () {
    'use strict';

    var cfg = window.SETSUNA_PORTAL || {};

    // ------------------------------------------------------------------
    // Penampil satu momen
    // ------------------------------------------------------------------

    var box = document.getElementById('lightbox');
    var image = document.getElementById('lightbox-image');
    var video = document.getElementById('lightbox-video');
    var by = document.getElementById('lightbox-by');
    var download = document.getElementById('lightbox-download');
    var closeBtn = document.getElementById('lightbox-close');
    var boomerangFrame = null;

    function stopBoomerang() {
        if (boomerangFrame) {
            cancelAnimationFrame(boomerangFrame);
            boomerangFrame = null;
        }
    }

    /**
     * Boomerang diputar maju lalu mundur dengan menggeser currentTime;
     * playbackRate negatif tidak didukung browser mana pun.
     */
    function playBoomerang() {
        var direction = 1;
        var last = performance.now();

        video.muted = true;
        video.controls = false;
        video.play().catch(function () {});

        function step(now) {
            var delta = (now - last) / 1000;
            last = now;

            if (direction === 1) {
                if (video.currentTime >= (video.duration || 1) - 0.06) {
                    direction = -1;
                    video.pause();
                }
            } else {
                video.currentTime = Math.max(0, video.currentTime - delta);

                if (video.currentTime <= 0.04) {
                    direction = 1;
                    video.play().catch(function () {});
                }
            }

            boomerangFrame = requestAnimationFrame(step);
        }

        boomerangFrame = requestAnimationFrame(step);
    }

    function openLightbox(button) {
        if (!box) {
            return;
        }

        var type = button.getAttribute('data-type');
        var preset = button.getAttribute('data-preset');
        var link = button.getAttribute('data-download');

        stopBoomerang();

        by.textContent = 'Dijepret oleh ' + button.getAttribute('data-by');
        download.hidden = !link;
        download.href = link || '#';

        if (type === 'photo') {
            video.hidden = true;
            video.removeAttribute('src');
            image.hidden = false;
            image.src = button.getAttribute('data-src');
            image.style.filter = 'none';
        } else {
            image.hidden = true;
            image.removeAttribute('src');
            video.hidden = false;
            video.src = button.getAttribute('data-src');
            video.poster = button.getAttribute('data-preview') || '';
            video.style.filter = preset;
            video.loop = type === 'video';
            video.controls = type === 'video';
            video.muted = type !== 'video';

            if (type === 'boomerang') {
                video.addEventListener('loadedmetadata', playBoomerang, { once: true });
            } else {
                video.play().catch(function () {});
            }
        }

        box.hidden = false;
        document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
        if (!box) {
            return;
        }

        stopBoomerang();
        box.hidden = true;
        video.pause();
        video.removeAttribute('src');
        image.removeAttribute('src');
        document.body.style.overflow = '';
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-lightbox]');

        if (button) {
            openLightbox(button);
        }
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', closeLightbox);
    }

    if (box) {
        box.addEventListener('click', function (event) {
            if (event.target === box || event.target.classList.contains('lightbox__stage')) {
                closeLightbox();
            }
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && box && !box.hidden) {
            closeLightbox();
        }
    });

    // ------------------------------------------------------------------
    // Album yang menyala sendiri
    // ------------------------------------------------------------------

    var grid = document.querySelector('[data-live-grid]');

    if (!grid || !cfg.feed) {
        return;
    }

    var scope = grid.getAttribute('data-live-grid') || 'all';
    var since = cfg.now || null;
    var seen = {};
    var timer = null;

    // Yang sudah tampil dari server tidak boleh ditambahkan lagi.
    Array.prototype.forEach.call(grid.querySelectorAll('[data-media-id]'), function (node) {
        seen[node.getAttribute('data-media-id')] = true;
    });

    function tileHtml(item) {
        var isPhoto = item.type === 'photo';
        var badge = isPhoto
            ? ''
            : '<span class="tile__type">' + (item.type === 'video' ? '▶ video' : '∞ boomerang') + '</span>';

        var dl = item.download
            ? '<a class="tile__dl" href="' + item.download + '" download aria-label="Unduh jepretan ' + item.by + '">'
              + '<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.3"'
              + ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
              + '<path d="M12 3v12"/><path d="m7 11 5 5 5-5"/><path d="M4 20h16"/></svg></a>'
            : '';

        return '<div class="tile tile--fresh" data-media-id="' + item.id + '"'
            + ' style="filter:' + (isPhoto ? 'none' : item.preset) + '">'
            + '<button type="button" class="tile__open" data-lightbox'
            + ' data-type="' + item.type + '"'
            + ' data-src="' + item.url + '"'
            + ' data-preview="' + item.preview + '"'
            + ' data-by="' + item.by + '"'
            + ' data-preset="' + item.preset + '"'
            + ' data-download="' + (item.download || '') + '">'
            + '<img src="' + item.preview + '" alt="Jepretan ' + item.by + '" loading="lazy" />'
            + badge
            + '<span class="tile__by"><i></i>' + item.by + '</span>'
            + '</button>' + dl + '</div>';
    }

    function pull() {
        var url = cfg.feed + '?scope=' + scope + (since ? '&since=' + encodeURIComponent(since) : '');

        fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                since = data.server_time || since;

                if (!data.media || !data.media.length) {
                    return;
                }

                // Server mengirim terbaru dulu; dibalik supaya urutan
                // penyisipan di atas grid tetap kronologis.
                data.media.slice().reverse().forEach(function (item) {
                    if (seen[item.id]) {
                        return;
                    }

                    seen[item.id] = true;
                    grid.insertAdjacentHTML('afterbegin', tileHtml(item));
                });

                var empty = document.querySelector('[data-live-empty]');

                if (empty) {
                    empty.hidden = true;
                }
            })
            .catch(function () {
                // Sinyal venue naik-turun; percobaan berikutnya saja.
            });
    }

    function start() {
        stop();
        // Cukup sering untuk terasa langsung, cukup jarang untuk tidak
        // membebani server saat ratusan tamu membuka album bersamaan.
        timer = setInterval(pull, 8000);
    }

    function stop() {
        if (timer) {
            clearInterval(timer);
            timer = null;
        }
    }

    // Berhenti menarik data saat tab disembunyikan.
    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            stop();
        } else {
            pull();
            start();
        }
    });

    start();
})();
