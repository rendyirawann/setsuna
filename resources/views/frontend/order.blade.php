@extends('frontend.layout')

@section('title', 'Pesan paket ' . $plan->name)

@section('content')

    <section class="section" style="padding-top:clamp(46px,7vw,80px)">
        <div class="wrap">
            <div class="grid" style="grid-template-columns:1.25fr 0.75fr;gap:44px;align-items:start">

                {{-- ------------------------------------------------ Formulir --}}
                <div>
                    <span class="eyebrow">Langkah terakhir</span>
                    <h2 style="margin-bottom:1.4rem">Siapkan portal acaramu</h2>
                    <p class="muted" style="margin-bottom:2rem">
                        Isi data di bawah. Kami langsung menyiapkan alamat portal, papan QR, dan
                        album acaramu — tinggal menunggu pembayaran dikonfirmasi.
                    </p>

                    @if ($errors->any())
                        <div class="alert alert--error">
                            Periksa lagi isiannya:
                            <ul style="margin:8px 0 0 18px;padding:0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('order.store', $plan->slug) }}">
                        @csrf

                        <h3 style="font-size:1.15rem;margin:2rem 0 1rem">Data pemesan</h3>

                        <div class="field-row">
                            <div class="field">
                                <label for="name">Nama pemesan</label>
                                <input type="text" id="name" name="name" value="{{ old('name') }}"
                                    placeholder="Nama lengkap" required />
                            </div>
                            <div class="field">
                                <label for="phone">Nomor WhatsApp</label>
                                <input type="text" id="phone" name="phone" value="{{ old('phone') }}"
                                    placeholder="08xxxxxxxxxx" required />
                            </div>
                        </div>

                        <div class="field-row">
                            <div class="field">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" value="{{ old('email') }}"
                                    placeholder="nama@email.com" required />
                            </div>
                            <div class="field">
                                <label for="instagram">Instagram (opsional)</label>
                                <input type="text" id="instagram" name="instagram" value="{{ old('instagram') }}"
                                    placeholder="@akunmu" />
                            </div>
                        </div>

                        <h3 style="font-size:1.15rem;margin:2.4rem 0 1rem">Detail acara</h3>

                        <div class="field-row">
                            <div class="field">
                                <label for="bride_name">Nama pertama</label>
                                <input type="text" id="bride_name" name="bride_name" value="{{ old('bride_name') }}"
                                    placeholder="Mempelai wanita / nama acara" required />
                                <div class="hint">Untuk acara non-pernikahan, isi nama acaranya.</div>
                            </div>
                            <div class="field">
                                <label for="groom_name">Nama kedua</label>
                                <input type="text" id="groom_name" name="groom_name" value="{{ old('groom_name') }}"
                                    placeholder="Mempelai pria / penyelenggara" required />
                            </div>
                        </div>

                        <div class="field-row">
                            <div class="field">
                                <label for="event_date">Tanggal acara</label>
                                <input type="date" id="event_date" name="event_date" value="{{ old('event_date') }}"
                                    min="{{ now()->toDateString() }}" required />
                            </div>
                            <div class="field">
                                <label for="city">Kota</label>
                                <input type="text" id="city" name="city" value="{{ old('city') }}"
                                    placeholder="Bandung" />
                            </div>
                        </div>

                        <div class="field">
                            <label for="venue">Lokasi / venue</label>
                            <input type="text" id="venue" name="venue" value="{{ old('venue') }}"
                                placeholder="Nama gedung atau alamat singkat" />
                        </div>

                        <div class="field">
                            <label for="slug">Alamat portal yang diinginkan</label>
                            <input type="text" id="slug" name="slug" value="{{ old('slug') }}"
                                placeholder="pernikahan-emma" pattern="[a-z0-9][a-z0-9\-]*" />
                            <div class="hint">
                                {{ rtrim(config('app.url'), '/') }}/<b id="slug-preview">alamat-acaramu</b> —
                                kosongkan saja kalau mau kami buatkan otomatis.
                            </div>
                        </div>

                        <div class="field">
                            <label for="notes">Catatan (opsional)</label>
                            <textarea id="notes" name="notes" rows="3"
                                placeholder="Permintaan khusus, jadwal pemasangan papan QR, dll.">{{ old('notes') }}</textarea>
                        </div>

                        <button type="submit" class="btn btn--gold btn--block" style="margin-top:12px">
                            Buat pesanan
                        </button>

                        <p class="small muted center" style="margin-top:14px">
                            Belum ada pembayaran di langkah ini. Kami kirim instruksi setelah pesanan dibuat.
                        </p>
                    </form>
                </div>

                {{-- ------------------------------------------------ Ringkasan --}}
                <aside class="plan" style="position:sticky;top:96px">
                    <span class="eyebrow" style="margin-bottom:.6rem">Paket dipilih</span>
                    <h3>{{ $plan->name }}</h3>
                    <p class="tagline">{{ $plan->tagline }}</p>

                    <div class="plan__price">{{ $plan->price_label }}</div>
                    <div class="plan__note">Sekali bayar · satu acara</div>

                    <ul>
                        @foreach ($plan->features ?? [] as $feature)
                            <li>{{ $feature }}</li>
                        @endforeach
                    </ul>

                    <div style="border-top:1px solid var(--line);padding-top:16px">
                        <p class="small muted" style="margin:0">Ganti paket:</p>
                        <div class="chips" style="margin-top:10px">
                            @foreach ($plans as $option)
                                <a href="{{ route('order.create', $option->slug) }}"
                                    class="chip {{ $option->id === $plan->id ? 'chip--on' : '' }}">
                                    {{ $option->name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </section>

@endsection

@push('scripts')
    <script>
        // Pratinjau alamat portal sambil mengetik.
        (function () {
            var slug = document.getElementById('slug');
            var bride = document.getElementById('bride_name');
            var groom = document.getElementById('groom_name');
            var preview = document.getElementById('slug-preview');

            function slugify(value) {
                return value.toLowerCase().trim()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/[\s_-]+/g, '-')
                    .replace(/^-+|-+$/g, '');
            }

            function render() {
                var manual = slugify(slug.value);
                var auto = slugify((bride.value + '-' + groom.value).replace(/^-|-$/g, ''));
                preview.textContent = manual || auto || 'alamat-acaramu';
            }

            [slug, bride, groom].forEach(function (input) {
                input.addEventListener('input', render);
            });

            render();
        })();
    </script>
@endpush
