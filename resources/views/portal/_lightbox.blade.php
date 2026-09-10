{{-- Penampil layar penuh untuk satu momen. Boomerang diputar bolak-balik
     karena berkasnya video pendek biasa. --}}

<div id="lightbox" hidden
    style="position:fixed;inset:0;z-index:500;background:rgba(6,5,5,.96);backdrop-filter:blur(20px);
           display:flex;flex-direction:column;padding:18px">

    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:14px">
        <span id="lightbox-by" class="small muted"></span>
        <div style="display:flex;gap:10px">
            <a id="lightbox-download" class="btn btn--ghost btn--sm" href="#" download>Unduh</a>
            <button type="button" id="lightbox-close" class="btn btn--ghost btn--sm">Tutup ✕</button>
        </div>
    </div>

    <div style="flex:1;display:grid;place-items:center;min-height:0">
        <img id="lightbox-image" alt="" style="max-width:100%;max-height:100%;object-fit:contain;border-radius:12px" hidden />
        <video id="lightbox-video" playsinline controls
            style="max-width:100%;max-height:100%;border-radius:12px" hidden></video>
    </div>
</div>

@push('scripts')
    <script>
        (function () {
            var box = document.getElementById('lightbox');
            var image = document.getElementById('lightbox-image');
            var video = document.getElementById('lightbox-video');
            var by = document.getElementById('lightbox-by');
            var download = document.getElementById('lightbox-download');
            var boomerangTimer = null;

            function stopBoomerang() {
                if (boomerangTimer) {
                    cancelAnimationFrame(boomerangTimer);
                    boomerangTimer = null;
                }
            }

            /* Boomerang: maju sampai ujung, lalu mundur dengan menggeser
               currentTime — playbackRate negatif tidak didukung browser. */
            function playBoomerang() {
                var direction = 1;
                var lastFrame = performance.now();

                video.muted = true;
                video.controls = false;
                video.play().catch(function () {});

                function step(now) {
                    var delta = (now - lastFrame) / 1000;
                    lastFrame = now;

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

                    boomerangTimer = requestAnimationFrame(step);
                }

                boomerangTimer = requestAnimationFrame(step);
            }

            function open(button) {
                var type = button.getAttribute('data-type');
                var preset = button.getAttribute('data-preset');
                var link = button.getAttribute('data-download');

                by.textContent = 'Dijepret oleh ' + button.getAttribute('data-by');
                download.hidden = !link;
                download.href = link || '#';

                stopBoomerang();

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

            function close() {
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
                    open(button);
                }
            });

            document.getElementById('lightbox-close').addEventListener('click', close);

            box.addEventListener('click', function (event) {
                if (event.target === box) {
                    close();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && !box.hidden) {
                    close();
                }
            });
        })();
    </script>
@endpush
