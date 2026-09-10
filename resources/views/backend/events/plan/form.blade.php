@extends('backend.layout.app')
@section('title', $plan->exists ? 'Ubah Paket' : 'Paket Baru')

@section('content')

    @include('backend.events._header', [
        'heading' => $plan->exists ? 'Ubah Paket' : 'Paket Baru',
        'crumbs' => ['Manajemen Acara', 'Paket', $plan->exists ? $plan->name : 'Buat Baru'],
    ])

    <div class="app-content flex-column-fluid">
        <div class="app-container container-xxl">

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0 ps-4">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ $plan->exists ? route('plans.update', $plan) : route('plans.store') }}">
                @csrf
                @if ($plan->exists)
                    @method('PUT')
                @endif

                <div class="row g-5">
                    <div class="col-lg-8">
                        <div class="card card-flush mb-5">
                            <div class="card-header"><h3 class="card-title">Informasi Paket</h3></div>
                            <div class="card-body pt-0">
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label required">Nama paket</label>
                                        <input type="text" name="name" required class="form-control form-control-solid"
                                            value="{{ old('name', $plan->name) }}" placeholder="Signature" />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Slug</label>
                                        <input type="text" name="slug" class="form-control form-control-solid"
                                            value="{{ old('slug', $plan->slug) }}" placeholder="otomatis dari nama" />
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Tagline</label>
                                        <input type="text" name="tagline" class="form-control form-control-solid"
                                            value="{{ old('tagline', $plan->tagline) }}"
                                            placeholder="Paket favorit untuk resepsi gedung." />
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Deskripsi</label>
                                        <textarea name="description" rows="2"
                                            class="form-control form-control-solid">{{ old('description', $plan->description) }}</textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Daftar fitur</label>
                                        <textarea name="features" rows="7" class="form-control form-control-solid"
                                            placeholder="Satu fitur per baris">{{ old('features', implode("\n", $plan->features ?? [])) }}</textarea>
                                        <div class="form-text">Satu baris = satu poin di kartu harga.</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card card-flush">
                            <div class="card-header"><h3 class="card-title">Jatah &amp; Kapasitas</h3></div>
                            <div class="card-body pt-0">
                                <div class="row g-4">
                                    <div class="col-md-3">
                                        <label class="form-label required">Foto</label>
                                        <input type="number" name="photo_quota" min="1" max="500" required
                                            class="form-control form-control-solid"
                                            value="{{ old('photo_quota', $plan->photo_quota ?? 18) }}" />
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label required">Video</label>
                                        <input type="number" name="video_quota" min="0" max="100" required
                                            class="form-control form-control-solid"
                                            value="{{ old('video_quota', $plan->video_quota ?? 2) }}" />
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label required">Durasi video</label>
                                        <input type="number" name="video_duration" min="3" max="60" required
                                            class="form-control form-control-solid"
                                            value="{{ old('video_duration', $plan->video_duration ?? 15) }}" />
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label required">Boomerang</label>
                                        <input type="number" name="boomerang_quota" min="0" max="100" required
                                            class="form-control form-control-solid"
                                            value="{{ old('boomerang_quota', $plan->boomerang_quota ?? 1) }}" />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Batas tamu</label>
                                        <input type="number" name="max_guests" min="1"
                                            class="form-control form-control-solid"
                                            value="{{ old('max_guests', $plan->max_guests) }}"
                                            placeholder="kosong = tanpa batas" />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label required">Masa simpan (hari)</label>
                                        <input type="number" name="storage_days" min="7" max="3650" required
                                            class="form-control form-control-solid"
                                            value="{{ old('storage_days', $plan->storage_days ?? 90) }}" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card card-flush mb-5">
                            <div class="card-header"><h3 class="card-title">Harga</h3></div>
                            <div class="card-body pt-0">
                                <div class="mb-4">
                                    <label class="form-label required">Harga (Rp)</label>
                                    <input type="number" name="price" min="0" step="1000" required
                                        class="form-control form-control-solid"
                                        value="{{ old('price', (int) $plan->price) }}" />
                                </div>
                                <div class="mb-4">
                                    <label class="form-label">Harga coret (Rp)</label>
                                    <input type="number" name="compare_at_price" min="0" step="1000"
                                        class="form-control form-control-solid"
                                        value="{{ old('compare_at_price', $plan->compare_at_price ? (int) $plan->compare_at_price : null) }}" />
                                </div>
                                <div>
                                    <label class="form-label">Urutan tampil</label>
                                    <input type="number" name="sort_order" min="0" max="999"
                                        class="form-control form-control-solid"
                                        value="{{ old('sort_order', $plan->sort_order ?? 0) }}" />
                                </div>
                            </div>
                        </div>

                        <div class="card card-flush mb-5">
                            <div class="card-header"><h3 class="card-title">Tampilan</h3></div>
                            <div class="card-body pt-0">
                                <label class="form-check form-switch form-check-custom form-check-solid mb-3">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                        @checked(old('is_active', $plan->is_active ?? true)) />
                                    <span class="form-check-label">Aktif (tampil di halaman harga)</span>
                                </label>
                                <label class="form-check form-switch form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" name="is_featured" value="1"
                                        @checked(old('is_featured', $plan->is_featured ?? false)) />
                                    <span class="form-check-label">Tandai sebagai paket unggulan</span>
                                </label>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                {{ $plan->exists ? 'Simpan Perubahan' : 'Buat Paket' }}
                            </button>
                            <a href="{{ route('plans.index') }}" class="btn btn-light">Batal</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

@endsection
