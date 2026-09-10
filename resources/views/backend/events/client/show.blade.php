@extends('backend.layout.app')
@section('title', $client->name)

@section('content')

    @include('backend.events._header', [
        'heading' => $client->name,
        'crumbs' => ['Manajemen Acara', 'Klien', 'Detail'],
    ])

    <div class="app-content flex-column-fluid">
        <div class="app-container container-xxl">
            <div class="row g-5">

                <div class="col-lg-4">
                    <div class="card card-flush">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-5">
                                <div class="symbol symbol-circle symbol-60px me-4">
                                    <div class="symbol-label fs-2 bg-light-primary text-primary fw-bold">
                                        {{ $client->initials }}
                                    </div>
                                </div>
                                <div>
                                    <div class="fs-4 fw-bold text-gray-900">{{ $client->name }}</div>
                                    <div class="text-muted fs-8">{{ $client->city }}</div>
                                </div>
                            </div>

                            <div class="d-flex flex-column gap-3 fs-7">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Email</span>
                                    <span class="fw-bold">{{ $client->email ?: '-' }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">WhatsApp</span>
                                    <span class="fw-bold">{{ $client->phone ?: '-' }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Instagram</span>
                                    <span class="fw-bold">{{ $client->instagram ?: '-' }}</span>
                                </div>
                            </div>

                            @if ($client->notes)
                                <div class="separator my-4"></div>
                                <p class="text-muted fs-8 mb-0">{{ $client->notes }}</p>
                            @endif

                            <div class="separator my-4"></div>

                            <a href="{{ route('clients.edit', $client) }}" class="btn btn-light-warning btn-sm w-100">
                                Ubah data
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card card-flush mb-5">
                        <div class="card-header">
                            <h3 class="card-title">Acara</h3>
                            <div class="card-toolbar">
                                <a href="{{ route('events.create') }}" class="btn btn-sm btn-light-primary">Acara baru</a>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            @forelse ($client->events as $event)
                                <div class="d-flex align-items-center justify-content-between py-3 border-bottom">
                                    <div>
                                        <a href="{{ route('events.show', $event) }}" class="fw-bold text-gray-900">
                                            {{ $event->title }}
                                        </a>
                                        <div class="text-muted fs-8">
                                            /{{ $event->slug }} · {{ $event->guests_count }} tamu ·
                                            {{ $event->media_count }} media
                                        </div>
                                    </div>
                                    <span class="badge badge-light-{{ $event->status_badge }}">
                                        {{ $event->status_label }}
                                    </span>
                                </div>
                            @empty
                                <div class="text-muted text-center py-10">Klien ini belum punya acara.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="card card-flush">
                        <div class="card-header"><h3 class="card-title">Pesanan</h3></div>
                        <div class="card-body pt-0">
                            @forelse ($client->subscriptions as $sub)
                                <div class="d-flex align-items-center justify-content-between py-3 border-bottom">
                                    <div>
                                        <a href="{{ route('subscriptions.show', $sub) }}" class="fw-bold text-gray-900">
                                            {{ $sub->invoice_number }}
                                        </a>
                                        <div class="text-muted fs-8">{{ $sub->plan?->name }} · {{ $sub->total_label }}</div>
                                    </div>
                                    <span class="badge badge-light-{{ $sub->status_badge }}">{{ $sub->status_label }}</span>
                                </div>
                            @empty
                                <div class="text-muted text-center py-10">Belum ada pesanan.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
