@extends('backend.layout.app')
@section('title', $event->exists ? 'Ubah Acara' : 'Acara Baru')

@section('content')

    @include('backend.events._header', [
        'heading' => $event->exists ? 'Ubah Acara' : 'Acara Baru',
        'crumbs' => ['Manajemen Acara', $event->exists ? $event->title : 'Buat Baru'],
    ])

    <div class="app-content flex-column-fluid">
        <div class="app-container container-xxl">

            @if ($errors->any())
                <div class="alert alert-danger d-flex flex-column">
                    <span class="fw-bold mb-2">Periksa lagi isiannya:</span>
                    <ul class="mb-0 ps-4">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" enctype="multipart/form-data"
                action="{{ $event->exists ? route('events.update', $event) : route('events.store') }}">
                @csrf
                @if ($event->exists)
                    @method('PUT')
                @endif

                <div class="row g-5">

                    {{-- ------------------------------------------- Kolom kiri --}}
                    <div class="col-lg-8">

                        <div class="card card-flush mb-5">
                            <div class="card-header"><h3 class="card-title">Identitas Acara</h3></div>
                            <div class="card-body pt-0">

                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label required">Klien</label>
                                        <select name="client_id" class="form-select form-select-solid" required>
                                            <option value="">— pilih klien —</option>
                                            @foreach ($clients as $client)
                                                <option value="{{ $client->id }}"
                                                    @selected(old('client_id', $event->client_id) == $client->id)>
                                                    {{ $client->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="form-text">
                                            Belum ada? <a href="{{ route('clients.create') }}">tambah klien</a>.
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Paket</label>
                                        <select name="plan_id" class="form-select form-select-solid">
                                            <option value="">— tanpa paket —</option>
                                            @foreach ($plans as $plan)
                                                <option value="{{ $plan->id }}"
                                                    data-photo="{{ $plan->photo_quota }}"
                                                    data-video="{{ $plan->video_quota }}"
                                                    data-duration="{{ $plan->video_duration }}"
                                                    data-boomerang="{{ $plan->boomerang_quota }}"
                                                    data-guests="{{ $plan->max_guests }}"
                                                    @selected(old('plan_id', $event->plan_id) == $plan->id)>
                                                    {{ $plan->name }} — {{ $plan->price_label }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="form-text">Memilih paket mengisi kuota di bawah otomatis.</div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label required">Jenis acara</label>
                                        <select name="event_type" class="form-select form-select-solid" required>
                                            @foreach (\App\Models\Event::TYPES as $value => $label)
                                                <option value="{{ $value }}"
                                                    @selected(old('event_type', $event->event_type ?? 'wedding') === $value)>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label required">Judul acara</label>
                                        <input type="text" name="title" class="form-control form-control-solid"
                                            value="{{ old('title', $event->title) }}" required
                                            placeholder="Pernikahan Emma &amp; Rio" />
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Nama pertama</label>
                                        <input type="text" name="bride_name" class="form-control form-control-solid"
                                            value="{{ old('bride_name', $event->bride_name) }}" placeholder="Mempelai wanita" />
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Nama kedua</label>
                                        <input type="text" name="groom_name" class="form-control form-control-solid"
                                            value="{{ old('groom_name', $event->groom_name) }}" placeholder="Mempelai pria" />
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Tuan rumah</label>
                                        <input type="text" name="host_name" class="form-control form-control-solid"
                                            value="{{ old('host_name', $event->host_name) }}"
                                            placeholder="Kampus / sekolah / kantor" />
                                        <div class="form-text">Dipakai acara non-pernikahan.</div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label required">Alamat portal (slug)</label>
                                        <div class="input-group input-group-solid">
                                            <span class="input-group-text">{{ rtrim(config('app.url'), '/') }}/</span>
                                            <input type="text" name="slug" class="form-control form-control-solid"
                                                value="{{ old('slug', $event->slug) }}" required
                                                pattern="[a-z0-9][a-z0-9\-]*" placeholder="pernikahan-emma" />
                                        </div>
                                        <div class="form-text">Huruf kecil, angka, dan tanda hubung.</div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Tagar</label>
                                        <input type="text" name="hashtag" class="form-control form-control-solid"
                                            value="{{ old('hashtag', $event->hashtag) }}" placeholder="#EmmaRioForever" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card card-flush mb-5">
                            <div class="card-header"><h3 class="card-title">Waktu &amp; Tempat</h3></div>
                            <div class="card-body pt-0">
                                <div class="row g-4">
                                    <div class="col-md-4">
                                        <label class="form-label">Tanggal acara</label>
                                        <input type="date" name="event_date" class="form-control form-control-solid"
                                            value="{{ old('event_date', $event->event_date?->toDateString()) }}" />
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Kamera dibuka</label>
                                        <input type="datetime-local" name="capture_opens_at" class="form-control form-control-solid"
                                            value="{{ old('capture_opens_at', $event->capture_opens_at?->format('Y-m-d\TH:i')) }}" />
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Kamera ditutup</label>
                                        <input type="datetime-local" name="capture_closes_at" class="form-control form-control-solid"
                                            value="{{ old('capture_closes_at', $event->capture_closes_at?->format('Y-m-d\TH:i')) }}" />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Venue</label>
                                        <input type="text" name="venue" class="form-control form-control-solid"
                                            value="{{ old('venue', $event->venue) }}" />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Kota</label>
                                        <input type="text" name="city" class="form-control form-control-solid"
                                            value="{{ old('city', $event->city) }}" />
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Alamat lengkap</label>
                                        <textarea name="address" rows="2" class="form-control form-control-solid">{{ old('address', $event->address) }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card card-flush">
                            <div class="card-header"><h3 class="card-title">Sambutan &amp; Papan QR</h3></div>
                            <div class="card-body pt-0">
                                <div class="mb-4">
                                    <label class="form-label">Sampul portal</label>
                                    <input type="file" name="cover" accept="image/*" class="form-control form-control-solid" />
                                    @if ($event->coverUrl())
                                        <img src="{{ $event->coverUrl() }}" class="rounded mt-3" style="max-height:120px" alt="" />
                                    @endif
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">Pesan sambutan (tampil di portal &amp; kamera)</label>
                                    <textarea name="welcome_message" rows="2"
                                        class="form-control form-control-solid">{{ old('welcome_message', $event->welcome_message) }}</textarea>
                                </div>

                                <div>
                                    <label class="form-label">Teks papan QR</label>
                                    <textarea name="sign_message" rows="2" class="form-control form-control-solid"
                                        placeholder="Kami tidak menyediakan fotografer. Ambil 18 foto sepanjang malam Anda!">{{ old('sign_message', $event->sign_message) }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ------------------------------------------ Kolom kanan --}}
                    <div class="col-lg-4">

                        <div class="card card-flush mb-5">
                            <div class="card-header"><h3 class="card-title">Status</h3></div>
                            <div class="card-body pt-0">
                                <select name="status" class="form-select form-select-solid">
                                    @foreach (\App\Models\Event::STATUSES as $status)
                                        <option value="{{ $status }}" @selected(old('status', $event->status) === $status)>
                                            {{ ucfirst($status) }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Kamera tamu hanya menerima kiriman saat status "active".</div>
                            </div>
                        </div>

                        <div class="card card-flush mb-5">
                            <div class="card-header"><h3 class="card-title">Jatah per Tamu</h3></div>
                            <div class="card-body pt-0">
                                <div class="row g-3">
                                    <div class="col-6">
                                        <label class="form-label required">Foto</label>
                                        <input type="number" name="photo_quota" id="q-photo" min="1" max="500" required
                                            class="form-control form-control-solid"
                                            value="{{ old('photo_quota', $event->photo_quota) }}" />
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label required">Video</label>
                                        <input type="number" name="video_quota" id="q-video" min="0" max="100" required
                                            class="form-control form-control-solid"
                                            value="{{ old('video_quota', $event->video_quota) }}" />
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label required">Durasi video (detik)</label>
                                        <input type="number" name="video_duration" id="q-duration" min="3" max="60" required
                                            class="form-control form-control-solid"
                                            value="{{ old('video_duration', $event->video_duration) }}" />
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label required">Boomerang</label>
                                        <input type="number" name="boomerang_quota" id="q-boomerang" min="0" max="100" required
                                            class="form-control form-control-solid"
                                            value="{{ old('boomerang_quota', $event->boomerang_quota) }}" />
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Batas jumlah tamu</label>
                                        <input type="number" name="max_guests" id="q-guests" min="1"
                                            class="form-control form-control-solid"
                                            value="{{ old('max_guests', $event->max_guests) }}" placeholder="kosong = tanpa batas" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card card-flush mb-5">
                            <div class="card-header"><h3 class="card-title">Tampilan &amp; Privasi</h3></div>
                            <div class="card-body pt-0">
                                <div class="mb-4">
                                    <label class="form-label required">Preset film</label>
                                    <select name="film_preset" class="form-select form-select-solid">
                                        @foreach ($presets as $key => $label)
                                            <option value="{{ $key }}"
                                                @selected(old('film_preset', $event->film_preset) === $key)>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label required">Kapan album dibuka</label>
                                    <select name="gallery_reveal" class="form-select form-select-solid">
                                        <option value="instant" @selected(old('gallery_reveal', $event->gallery_reveal) === 'instant')>
                                            Langsung, sejak acara mulai
                                        </option>
                                        <option value="after_event" @selected(old('gallery_reveal', $event->gallery_reveal) === 'after_event')>
                                            Otomatis setelah acara selesai
                                        </option>
                                        <option value="manual" @selected(old('gallery_reveal', $event->gallery_reveal) === 'manual')>
                                            Manual, dibuka dari admin
                                        </option>
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">Masa simpan media sampai</label>
                                    <input type="date" name="media_expires_at" class="form-control form-control-solid"
                                        value="{{ old('media_expires_at', $event->media_expires_at?->toDateString()) }}" />
                                </div>

                                @foreach ([
                                    'gallery_public' => ['Album bisa dilihat publik', $event->gallery_public ?? true],
                                    'require_guest_name' => ['Wajib isi nama tamu', $event->require_guest_name ?? true],
                                    'allow_download' => ['Tamu boleh mengunduh', $event->allow_download ?? true],
                                    'moderation' => ['Moderasi sebelum tayang', $event->moderation ?? false],
                                ] as $field => [$label, $checked])
                                    <label class="form-check form-switch form-check-custom form-check-solid mb-3">
                                        <input class="form-check-input" type="checkbox" name="{{ $field }}" value="1"
                                            @checked(old($field, $checked)) />
                                        <span class="form-check-label">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                {{ $event->exists ? 'Simpan Perubahan' : 'Buat Acara' }}
                            </button>
                            <a href="{{ route('events.index') }}" class="btn btn-light">Batal</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        // Memilih paket mengisi kuota, tapi angkanya tetap boleh ditimpa
        // manual — sering ada acara yang minta jatah khusus.
        document.querySelector('[name="plan_id"]').addEventListener('change', function () {
            var option = this.options[this.selectedIndex];

            if (!option.value) {
                return;
            }

            var map = {
                'q-photo': option.dataset.photo,
                'q-video': option.dataset.video,
                'q-duration': option.dataset.duration,
                'q-boomerang': option.dataset.boomerang,
                'q-guests': option.dataset.guests
            };

            Object.keys(map).forEach(function (id) {
                if (map[id] !== undefined && map[id] !== '') {
                    document.getElementById(id).value = map[id];
                }
            });
        });
    </script>
@endpush
