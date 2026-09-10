/* =========================================================================
   Kamera tamu SETSUNA.

   Alur singkat:
     scan QR -> halaman ini -> minta izin kamera -> (isi nama sekali)
     -> jepret -> unggah satu per satu -> kuota berkurang.

   Catatan desain:
   - Foto dibakar bersama preset film lewat canvas, jadi berkas yang
     tersimpan sama dengan yang dilihat tamu.
   - Video dan boomerang direkam apa adanya dan presetnya diterapkan
     saat pemutaran. Merekam lewat canvas memang bisa membakar filter,
     tapi rapuh di Safari iOS — dan mayoritas tamu memakai ponsel.
   - Semua unggahan berjalan satu per satu (antrean) supaya WiFi venue
     tidak tercekik saat banyak tamu memotret bersamaan.
   ========================================================================= */

(function () {
    'use strict';

    var cfg = window.SETSUNA_CAMERA;

    if (!cfg) {
        return;
    }

    var el = {
        video: document.getElementById('cam-video'),
        canvas: document.getElementById('cam-canvas'),
        shutter: document.getElementById('cam-shutter'),
        ring: document.getElementById('cam-ring'),
        flash: document.getElementById('cam-flash'),
        modes: Array.prototype.slice.call(document.querySelectorAll('[data-mode]')),
        reel: document.getElementById('cam-reel'),
        counterLabel: document.getElementById('cam-count-label'),
        toast: document.getElementById('cam-toast'),
        uploading: document.getElementById('cam-uploading'),
        gate: document.getElementById('cam-gate'),
        gateForm: document.getElementById('cam-gate-form'),
        gateName: document.getElementById('cam-gate-name'),
        gateBtn: document.getElementById('cam-gate-btn'),
        rollBtn: document.getElementById('cam-roll-btn'),
        rollThumb: document.getElementById('cam-roll-thumb'),
        rollCount: document.getElementById('cam-roll-count'),
        rollSheet: document.getElementById('cam-roll-sheet'),
        rollGrid: document.getElementById('cam-roll-grid'),
        rollClose: document.getElementById('cam-roll-close'),
        done: document.getElementById('cam-done'),
        doneQuota: document.getElementById('cam-done-quota'),
        flip: document.getElementById('cam-flip'),
        torch: document.getElementById('cam-torch'),
        error: document.getElementById('cam-error'),
        errorText: document.getElementById('cam-error-text'),
        retry: document.getElementById('cam-retry'),
        viewer: document.getElementById('cam-viewer'),
        viewerStage: document.querySelector('.cam__viewer-stage'),
        viewerImage: document.getElementById('cam-viewer-image'),
        viewerVideo: document.getElementById('cam-viewer-video'),
        viewerLabel: document.getElementById('cam-viewer-label'),
        viewerDownload: document.getElementById('cam-viewer-download'),
        viewerClose: document.getElementById('cam-viewer-close')
    };

    var state = {
        mode: 'photo',
        quota: cfg.quota,
        facing: 'environment',
        stream: null,
        track: null,
        torchOn: false,
        recorder: null,
        chunks: [],
        recording: false,
        recordTimer: null,
        recordStart: 0,
        busy: false,
        queue: [],
        sending: false,
        shots: 0,
        lastThumb: null
    };

    var RING_LENGTH = 2 * Math.PI * 41;

    // ------------------------------------------------------------------
    // Kamera
    // ------------------------------------------------------------------

    function startCamera() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            showError('Browser ini belum mendukung kamera. Coba buka lagi lewat Chrome atau Safari.');
            return;
        }

        stopCamera();

        var constraints = {
            audio: state.mode === 'video',
            video: {
                facingMode: { ideal: state.facing },
                width: { ideal: 1920 },
                height: { ideal: 1080 }
            }
        };

        navigator.mediaDevices.getUserMedia(constraints)
            .then(function (stream) {
                state.stream = stream;
                state.track = stream.getVideoTracks()[0];
                el.video.srcObject = stream;
                el.video.play().catch(function () { /* autoplay ditangani atribut playsinline */ });
                el.video.classList.toggle('is-mirrored', state.facing === 'user');
                el.error.hidden = true;
                updateTorchButton();
            })
            .catch(function (error) {
                var message = 'Kami butuh izin kamera untuk mulai memotret.';

                if (error && (error.name === 'NotAllowedError' || error.name === 'SecurityError')) {
                    message = 'Akses kamera ditolak. Buka pengaturan situs di browser, izinkan Kamera, lalu muat ulang halaman ini.';
                } else if (error && error.name === 'NotFoundError') {
                    message = 'Tidak ada kamera yang terdeteksi di perangkat ini.';
                } else if (location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
                    message = 'Kamera hanya bisa dibuka lewat koneksi aman (https).';
                }

                showError(message);
            });
    }

    function stopCamera() {
        if (state.stream) {
            state.stream.getTracks().forEach(function (track) { track.stop(); });
        }

        state.stream = null;
        state.track = null;
        state.torchOn = false;
    }

    function showError(message) {
        el.errorText.textContent = message;
        el.error.hidden = false;
    }

    function updateTorchButton() {
        var capabilities = state.track && state.track.getCapabilities ? state.track.getCapabilities() : null;
        var supported = !!(capabilities && capabilities.torch);

        el.torch.hidden = !supported;
        el.torch.classList.toggle('is-on', state.torchOn);
    }

    // ------------------------------------------------------------------
    // Kuota dan tampilan
    // ------------------------------------------------------------------

    function remaining(mode) {
        return state.quota[mode] || 0;
    }

    function totalRemaining() {
        return remaining('photo') + remaining('video') + remaining('boomerang');
    }

    function renderQuota() {
        el.modes.forEach(function (button) {
            var mode = button.getAttribute('data-mode');
            var left = remaining(mode);
            var counter = button.querySelector('small');

            if (counter) {
                counter.textContent = left + ' tersisa';
            }

            button.disabled = left <= 0;
            button.classList.toggle('is-on', mode === state.mode);
        });

        var left = remaining(state.mode);
        setCount(left);
        el.counterLabel.textContent = labelFor(state.mode);

        el.shutter.disabled = left <= 0 || state.busy;
        el.rollCount.textContent = state.shots;
        el.rollCount.hidden = state.shots === 0;

        if (totalRemaining() <= 0 && !state.busy) {
            showDone();
        }
    }

    /**
     * Penghitung bergulir.
     *
     * Gulungan berisi lima angka, kecil di atas besar di bawah, dengan
     * angka sekarang di tengah. Berkurang satu berarti gulungan turun
     * satu langkah: angka yang terpakai jatuh keluar di bawah dan
     * penggantinya masuk dari atas. Perubahan yang lompat (ganti mode,
     * kuota disegarkan dari server) digambar langsung tanpa animasi.
     */
    var SLOT = 26;
    var reelValue = null;
    var reelBusy = false;

    function paintReel(value) {
        var html = '';

        for (var offset = -2; offset <= 2; offset++) {
            var n = value + offset;
            var isNow = offset === 0;

            html += '<span class="cam__reel-n' + (isNow ? ' is-now' : '') + '">'
                + (n >= 0 ? n : '')
                + '</span>';
        }

        el.reel.innerHTML = html;
    }

    function restReel() {
        el.reel.style.transition = 'none';
        el.reel.style.transform = 'translateY(-' + SLOT + 'px)';
        // Paksa reflow supaya transisi berikutnya benar-benar berjalan.
        void el.reel.offsetHeight;
        el.reel.style.transition = '';
    }

    function setCount(value) {
        if (!el.reel) {
            return;
        }

        if (reelValue === value) {
            return;
        }

        var steppedDown = reelValue !== null && value === reelValue - 1;

        if (!steppedDown || reelBusy) {
            reelValue = value;
            paintReel(value);
            restReel();

            return;
        }

        reelBusy = true;
        reelValue = value;

        // Turunkan gulungan satu langkah, lalu gambar ulang di posisi diam.
        el.reel.style.transition = 'transform .5s cubic-bezier(.2,.9,.25,1)';
        el.reel.style.transform = 'translateY(0)';

        window.setTimeout(function () {
            paintReel(value);
            restReel();
            reelBusy = false;
        }, 520);
    }

    function labelFor(mode) {
        return mode === 'photo' ? 'foto tersisa' : (mode === 'video' ? 'video tersisa' : 'boomerang tersisa');
    }

    function showDone() {
        el.doneQuota.innerHTML = '';
        el.done.hidden = false;
    }

    function setMode(mode) {
        if (state.recording || state.busy || remaining(mode) <= 0) {
            return;
        }

        var needsAudio = mode === 'video';
        var hadAudio = state.mode === 'video';

        state.mode = mode;
        renderQuota();

        // Mode video butuh trek audio, jadi stream diminta ulang.
        if (needsAudio !== hadAudio) {
            startCamera();
        }
    }

    function toast(message, bad) {
        el.toast.textContent = message;
        el.toast.classList.toggle('is-bad', !!bad);
        el.toast.classList.add('is-on');

        clearTimeout(el.toast._timer);
        el.toast._timer = setTimeout(function () {
            el.toast.classList.remove('is-on');
        }, bad ? 4200 : 2400);
    }

    // ------------------------------------------------------------------
    // Menjepret foto
    // ------------------------------------------------------------------

    function takePhoto() {
        if (!state.stream || state.busy || remaining('photo') <= 0) {
            return;
        }

        state.busy = true;
        el.shutter.disabled = true;

        var width = el.video.videoWidth;
        var height = el.video.videoHeight;

        if (!width || !height) {
            state.busy = false;
            renderQuota();
            toast('Kamera belum siap, tunggu sebentar.', true);
            return;
        }

        el.canvas.width = width;
        el.canvas.height = height;

        var ctx = el.canvas.getContext('2d');

        ctx.save();

        if (state.facing === 'user') {
            ctx.translate(width, 0);
            ctx.scale(-1, 1);
        }

        // Preset film dibakar di sini, sama persis dengan pratinjau.
        ctx.filter = cfg.preset.css;
        ctx.drawImage(el.video, 0, 0, width, height);
        ctx.restore();

        ctx.filter = 'none';
        drawVignette(ctx, width, height);
        drawGrain(ctx, width, height);

        fireFlash();

        el.canvas.toBlob(function (blob) {
            if (!blob) {
                state.busy = false;
                renderQuota();
                toast('Gagal memproses foto.', true);
                return;
            }

            enqueue('photo', blob, 'jpg', null, null);
        }, 'image/jpeg', 0.92);
    }

    function drawVignette(ctx, width, height) {
        var strength = cfg.preset.vignette || 0;

        // Preset Natural sengaja tidak menambahkan apa pun.
        if (strength <= 0) {
            return;
        }

        var gradient = ctx.createRadialGradient(
            width / 2, height / 2, Math.min(width, height) * 0.34,
            width / 2, height / 2, Math.max(width, height) * 0.76
        );

        gradient.addColorStop(0, 'rgba(0,0,0,0)');
        gradient.addColorStop(1, 'rgba(0,0,0,' + strength + ')');

        ctx.fillStyle = gradient;
        ctx.fillRect(0, 0, width, height);
    }

    function drawGrain(ctx, width, height) {
        var amount = cfg.preset.grain || 0;

        if (amount <= 0) {
            return;
        }

        var dots = Math.round((width * height) / 620);

        ctx.save();
        ctx.globalAlpha = amount * 0.55;
        ctx.fillStyle = '#fff8ec';

        for (var i = 0; i < dots; i++) {
            ctx.fillRect(Math.random() * width, Math.random() * height, 1, 1);
        }

        ctx.globalAlpha = amount * 0.4;
        ctx.fillStyle = '#0d0b09';

        for (var j = 0; j < dots; j++) {
            ctx.fillRect(Math.random() * width, Math.random() * height, 1, 1);
        }

        ctx.restore();
    }

    function fireFlash() {
        el.flash.classList.remove('is-firing');
        void el.flash.offsetWidth;
        el.flash.classList.add('is-firing');
    }

    // ------------------------------------------------------------------
    // Merekam video dan boomerang
    // ------------------------------------------------------------------

    function pickMimeType() {
        var candidates = [
            'video/mp4;codecs=avc1',
            'video/mp4',
            'video/webm;codecs=vp9,opus',
            'video/webm;codecs=vp8,opus',
            'video/webm'
        ];

        for (var i = 0; i < candidates.length; i++) {
            if (window.MediaRecorder && MediaRecorder.isTypeSupported(candidates[i])) {
                return candidates[i];
            }
        }

        return '';
    }

    function startRecording() {
        if (!state.stream || state.busy || state.recording) {
            return;
        }

        if (!window.MediaRecorder) {
            toast('Browser ini belum mendukung perekaman video.', true);
            return;
        }

        var isBoomerang = state.mode === 'boomerang';
        var limitMs = isBoomerang ? 1500 : cfg.videoDuration * 1000;
        var mimeType = pickMimeType();

        // Boomerang tidak butuh suara.
        var source = state.stream;

        if (isBoomerang) {
            source = new MediaStream(state.stream.getVideoTracks());
        }

        try {
            state.recorder = mimeType
                ? new MediaRecorder(source, { mimeType: mimeType, videoBitsPerSecond: 3500000 })
                : new MediaRecorder(source);
        } catch (error) {
            toast('Perekaman tidak bisa dimulai di perangkat ini.', true);
            return;
        }

        state.chunks = [];
        state.recording = true;
        state.recordStart = Date.now();

        state.recorder.ondataavailable = function (event) {
            if (event.data && event.data.size > 0) {
                state.chunks.push(event.data);
            }
        };

        state.recorder.onstop = function () {
            var duration = Date.now() - state.recordStart;
            var type = state.chunks.length && state.chunks[0].type ? state.chunks[0].type : (mimeType || 'video/webm');
            var blob = new Blob(state.chunks, { type: type });
            var extension = type.indexOf('mp4') !== -1 ? 'mp4' : 'webm';

            state.recording = false;
            el.shutter.classList.remove('is-recording');
            setRingProgress(0);

            grabPoster(function (poster) {
                enqueue(isBoomerang ? 'boomerang' : 'video', blob, extension, poster, duration);
            });
        };

        state.recorder.start();
        el.shutter.classList.add('is-recording');
        tickRing(limitMs);

        state.recordTimer = setTimeout(stopRecording, limitMs);

        if (isBoomerang) {
            toast('Merekam boomerang...');
        }
    }

    function stopRecording() {
        if (!state.recording || !state.recorder) {
            return;
        }

        clearTimeout(state.recordTimer);
        state.busy = true;
        el.shutter.disabled = true;

        try {
            state.recorder.stop();
        } catch (error) {
            state.recording = false;
            state.busy = false;
            renderQuota();
        }
    }

    function tickRing(limitMs) {
        function frame() {
            if (!state.recording) {
                return;
            }

            var progress = Math.min(1, (Date.now() - state.recordStart) / limitMs);
            setRingProgress(progress);
            requestAnimationFrame(frame);
        }

        requestAnimationFrame(frame);
    }

    function setRingProgress(progress) {
        var circle = el.ring.querySelector('circle');
        circle.style.strokeDasharray = RING_LENGTH;
        circle.style.strokeDashoffset = RING_LENGTH * (1 - progress);
    }

    /** Ambil satu frame sebagai sampul video. */
    function grabPoster(callback) {
        var width = el.video.videoWidth;
        var height = el.video.videoHeight;

        if (!width || !height) {
            callback(null);
            return;
        }

        el.canvas.width = width;
        el.canvas.height = height;

        var ctx = el.canvas.getContext('2d');

        ctx.save();

        if (state.facing === 'user') {
            ctx.translate(width, 0);
            ctx.scale(-1, 1);
        }

        ctx.filter = cfg.preset.css;
        ctx.drawImage(el.video, 0, 0, width, height);
        ctx.restore();
        ctx.filter = 'none';
        drawVignette(ctx, width, height);

        el.canvas.toBlob(function (blob) { callback(blob); }, 'image/jpeg', 0.8);
    }

    // ------------------------------------------------------------------
    // Antrean unggah
    // ------------------------------------------------------------------

    function enqueue(type, blob, extension, poster, durationMs) {
        state.queue.push({ type: type, blob: blob, extension: extension, poster: poster, durationMs: durationMs });

        // Kuota dikurangi optimistis supaya penghitung terasa responsif;
        // jawaban server tetap yang menentukan angka akhirnya.
        state.quota[type] = Math.max(0, remaining(type) - 1);
        state.busy = false;
        renderQuota();

        pump();
    }

    function pump() {
        if (state.sending || !state.queue.length) {
            return;
        }

        state.sending = true;
        el.uploading.classList.add('is-on');

        var item = state.queue[0];
        var form = new FormData();

        form.append('_token', cfg.csrf);
        form.append('type', item.type);
        form.append('file', item.blob, 'capture.' + item.extension);
        form.append('facing', state.facing);
        form.append('film_preset', cfg.preset.key);

        if (item.poster) {
            form.append('poster', item.poster, 'poster.jpg');
        }

        if (item.durationMs) {
            form.append('duration_ms', Math.round(item.durationMs));
        }

        fetch(cfg.endpoints.capture, {
            method: 'POST',
            body: form,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (response) {
                return response.json().then(function (data) {
                    return { ok: response.ok, status: response.status, data: data };
                });
            })
            .then(function (result) {
                if (!result.ok) {
                    if (result.data && result.data.quota) {
                        state.quota = result.data.quota;
                    }

                    state.queue.shift();
                    toast(result.data && result.data.message ? result.data.message : 'Gagal mengunggah.', true);
                    renderQuota();
                    finishSend();
                    return;
                }

                state.queue.shift();
                state.quota = result.data.quota;
                state.shots += 1;
                setThumb(result.data.media);
                renderQuota();
                finishSend();
            })
            .catch(function () {
                // Jaringan venue sering putus-putus: coba lagi, jangan buang jepretan.
                item.retries = (item.retries || 0) + 1;

                if (item.retries >= 4) {
                    state.queue.shift();
                    toast('Satu jepretan gagal terkirim.', true);
                } else {
                    toast('Sinyal lemah, mencoba lagi...');
                }

                setTimeout(finishSend, 1800);
            });
    }

    function finishSend() {
        state.sending = false;

        if (!state.queue.length) {
            el.uploading.classList.remove('is-on');
        }

        pump();
    }

    function setThumb(media) {
        if (!media) {
            return;
        }

        state.lastThumb = media.preview || media.url;
        el.rollThumb.innerHTML = '<img src="' + state.lastThumb + '" alt="" />';
    }

    // ------------------------------------------------------------------
    // Roll pribadi
    // ------------------------------------------------------------------

    function openRoll() {
        el.rollSheet.hidden = false;
        el.rollGrid.innerHTML = '<p style="grid-column:1/-1">Memuat...</p>';

        fetch(cfg.endpoints.roll, { credentials: 'same-origin' })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (!data.media || !data.media.length) {
                    el.rollGrid.innerHTML = '<p style="grid-column:1/-1">Belum ada jepretan. Ayo mulai!</p>';
                    return;
                }

                // Tiap hasil dibungkus tombol supaya bisa dibuka besar —
                // video dan boomerang tidak bisa dinilai dari poster diam.
                el.rollGrid.innerHTML = data.media.map(function (item) {
                    var isPhoto = item.type === 'photo';
                    var tag = isPhoto
                        ? ''
                        : '<span class="tag">' + (item.type === 'video' ? 'video' : 'boom') + '</span>';
                    var play = isPhoto ? '' : '<span class="roll-play" aria-hidden="true">▶</span>';

                    return '<button type="button" class="roll-item" data-open-media'
                        + ' data-type="' + item.type + '"'
                        + ' data-url="' + item.url + '"'
                        + ' data-preview="' + (item.preview || '') + '"'
                        + ' style="filter:' + cfg.preset.css + '">'
                        + '<img src="' + (item.preview || item.url) + '" alt="" loading="lazy" />'
                        + tag + play
                        + '</button>';
                }).join('');
            })
            .catch(function () {
                el.rollGrid.innerHTML = '<p style="grid-column:1/-1">Gagal memuat roll.</p>';
            });
    }

    // ------------------------------------------------------------------
    // Pratinjau satu hasil
    // ------------------------------------------------------------------

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
    function playBoomerang(video) {
        var direction = 1;
        var last = performance.now();

        video.muted = true;
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

    function openViewer(button) {
        var type = button.getAttribute('data-type');
        var url = button.getAttribute('data-url');
        var preview = button.getAttribute('data-preview');

        stopBoomerang();

        el.viewerLabel.textContent = type === 'photo'
            ? 'Foto'
            : (type === 'video' ? 'Video' : 'Boomerang');

        el.viewerDownload.href = url;

        if (type === 'photo') {
            el.viewerVideo.hidden = true;
            el.viewerVideo.removeAttribute('src');
            el.viewerImage.hidden = false;
            el.viewerImage.src = url;
        } else {
            el.viewerImage.hidden = true;
            el.viewerImage.removeAttribute('src');
            el.viewerVideo.hidden = false;
            el.viewerVideo.src = url;

            if (preview) {
                el.viewerVideo.poster = preview;
            }

            el.viewerVideo.loop = type === 'video';
            el.viewerVideo.controls = type === 'video';
            el.viewerVideo.muted = type !== 'video';

            if (type === 'boomerang') {
                el.viewerVideo.addEventListener('loadedmetadata', function handler() {
                    el.viewerVideo.removeEventListener('loadedmetadata', handler);
                    playBoomerang(el.viewerVideo);
                });
            } else {
                el.viewerVideo.play().catch(function () {});
            }
        }

        // Filter film ikut diterapkan supaya pratinjau sama dengan galeri.
        el.viewerStage.style.filter = type === 'photo' ? 'none' : cfg.preset.css;
        el.viewer.hidden = false;
    }

    function closeViewer() {
        stopBoomerang();
        el.viewer.hidden = true;
        el.viewerVideo.pause();
        el.viewerVideo.removeAttribute('src');
        el.viewerImage.removeAttribute('src');
    }

    // ------------------------------------------------------------------
    // Gerbang nama
    // ------------------------------------------------------------------

    function submitName(event) {
        event.preventDefault();

        var name = (el.gateName.value || '').trim();

        if (cfg.requireName && name.length < 2) {
            el.gateName.focus();
            toast('Tulis namamu dulu ya.', true);
            return;
        }

        el.gateBtn.disabled = true;
        el.gateBtn.textContent = 'Menyiapkan kamera...';

        var form = new FormData();
        form.append('_token', cfg.csrf);
        form.append('name', name);

        fetch(cfg.endpoints.register, {
            method: 'POST',
            body: form,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (response) {
                return response.json().then(function (data) {
                    return { ok: response.ok, data: data };
                });
            })
            .then(function (result) {
                if (!result.ok) {
                    el.gateBtn.disabled = false;
                    el.gateBtn.textContent = 'Mulai memotret';
                    toast(result.data.message || 'Gagal memulai sesi.', true);
                    return;
                }

                state.quota = result.data.quota;
                el.gate.hidden = true;
                renderQuota();
                toast('Selamat memotret, ' + result.data.guest.name + '!');
            })
            .catch(function () {
                el.gateBtn.disabled = false;
                el.gateBtn.textContent = 'Mulai memotret';
                toast('Koneksi bermasalah. Coba lagi.', true);
            });
    }

    // ------------------------------------------------------------------
    // Pemasangan
    // ------------------------------------------------------------------

    el.modes.forEach(function (button) {
        button.addEventListener('click', function () {
            setMode(button.getAttribute('data-mode'));
        });
    });

    el.shutter.addEventListener('click', function () {
        if (state.mode === 'photo') {
            takePhoto();
            return;
        }

        if (state.recording) {
            stopRecording();
        } else {
            startRecording();
        }
    });

    el.flip.addEventListener('click', function () {
        if (state.recording) {
            return;
        }

        state.facing = state.facing === 'environment' ? 'user' : 'environment';
        startCamera();
    });

    el.torch.addEventListener('click', function () {
        if (!state.track || !state.track.applyConstraints) {
            return;
        }

        state.torchOn = !state.torchOn;

        state.track.applyConstraints({ advanced: [{ torch: state.torchOn }] })
            .then(updateTorchButton)
            .catch(function () {
                state.torchOn = false;
                updateTorchButton();
                toast('Lampu kilat tidak tersedia di perangkat ini.', true);
            });
    });

    el.rollBtn.addEventListener('click', openRoll);
    el.rollClose.addEventListener('click', function () { el.rollSheet.hidden = true; });

    // Ketuk satu hasil di roll untuk membukanya besar.
    el.rollGrid.addEventListener('click', function (event) {
        var button = event.target.closest('[data-open-media]');

        if (button) {
            openViewer(button);
        }
    });

    el.viewerClose.addEventListener('click', closeViewer);
    el.gateForm.addEventListener('submit', submitName);
    el.retry.addEventListener('click', startCamera);

    // Hemat baterai: matikan kamera saat tab disembunyikan.
    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            if (state.recording) {
                stopRecording();
            }

            stopCamera();
        } else if (!el.error.hidden === false || !state.stream) {
            startCamera();
        }
    });

    window.addEventListener('pagehide', stopCamera);

    if (cfg.guest) {
        el.gate.hidden = true;
    } else {
        el.gate.hidden = false;
    }

    renderQuota();
    startCamera();
})();
