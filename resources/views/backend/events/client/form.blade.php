@extends('backend.layout.app')
@section('title', $client->exists ? 'Ubah Klien' : 'Klien Baru')

@section('content')

    @include('backend.events._header', [
        'heading' => $client->exists ? 'Ubah Klien' : 'Klien Baru',
        'crumbs' => ['Manajemen Acara', 'Klien', $client->exists ? $client->name : 'Buat Baru'],
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

            <form method="POST" action="{{ $client->exists ? route('clients.update', $client) : route('clients.store') }}">
                @csrf
                @if ($client->exists)
                    @method('PUT')
                @endif

                <div class="card card-flush" style="max-width:820px">
                    <div class="card-header"><h3 class="card-title">Data Klien</h3></div>
                    <div class="card-body pt-0">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label required">Nama</label>
                                <input type="text" name="name" required class="form-control form-control-solid"
                                    value="{{ old('name', $client->name) }}" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control form-control-solid"
                                    value="{{ old('email', $client->email) }}" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nomor WhatsApp</label>
                                <input type="text" name="phone" class="form-control form-control-solid"
                                    value="{{ old('phone', $client->phone) }}" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Instagram</label>
                                <input type="text" name="instagram" class="form-control form-control-solid"
                                    value="{{ old('instagram', $client->instagram) }}" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kota</label>
                                <input type="text" name="city" class="form-control form-control-solid"
                                    value="{{ old('city', $client->city) }}" />
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan</label>
                                <textarea name="notes" rows="3"
                                    class="form-control form-control-solid">{{ old('notes', $client->notes) }}</textarea>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-5">
                            <button type="submit" class="btn btn-primary">
                                {{ $client->exists ? 'Simpan Perubahan' : 'Simpan Klien' }}
                            </button>
                            <a href="{{ route('clients.index') }}" class="btn btn-light">Batal</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

@endsection
