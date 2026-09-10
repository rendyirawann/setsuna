@extends('backend.layout.app')
@section('title', $subscription->invoice_number)

@section('content')

    @include('backend.events._header', [
        'heading' => $subscription->invoice_number,
        'crumbs' => ['Manajemen Acara', 'Pesanan', 'Detail'],
    ])

    <div class="app-content flex-column-fluid">
        <div class="app-container container-xxl">
            <div class="row g-5">

                <div class="col-lg-8">
                    <div class="card card-flush">
                        <div class="card-header">
                            <h3 class="card-title">Rincian</h3>
                            <div class="card-toolbar">
                                <span class="badge badge-light-{{ $subscription->status_badge }} fs-7">
                                    {{ $subscription->status_label }}
                                </span>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <table class="table align-middle fs-6 gy-3 mb-0">
                                <tbody>
                                    <tr>
                                        <td class="text-muted w-200px">Klien</td>
                                        <td class="fw-bold">
                                            <a href="{{ route('clients.show', $subscription->client) }}">
                                                {{ $subscription->client->name }}
                                            </a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Paket</td>
                                        <td class="fw-bold">{{ $subscription->plan?->name ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Acara</td>
                                        <td class="fw-bold">
                                            @if ($subscription->event)
                                                <a href="{{ route('events.show', $subscription->event) }}">
                                                    {{ $subscription->event->title }}
                                                </a>
                                            @else
                                                <span class="text-muted">belum ditautkan</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Nominal</td>
                                        <td>Rp {{ number_format((float) $subscription->amount, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Diskon</td>
                                        <td>Rp {{ number_format((float) $subscription->discount, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Total</td>
                                        <td class="fw-bold fs-4 text-gray-900">{{ $subscription->total_label }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Metode bayar</td>
                                        <td>{{ $subscription->payment_method ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Referensi</td>
                                        <td>{{ $subscription->payment_reference ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Dibayar pada</td>
                                        <td>{{ $subscription->paid_at?->translatedFormat('d F Y H:i') ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Jatuh tempo</td>
                                        <td>{{ $subscription->due_at?->translatedFormat('d F Y') ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Masa berlaku</td>
                                        <td>
                                            {{ $subscription->starts_at?->translatedFormat('d M Y') ?? '-' }}
                                            &rarr;
                                            {{ $subscription->ends_at?->translatedFormat('d M Y') ?? '-' }}
                                        </td>
                                    </tr>
                                    @if ($subscription->notes)
                                        <tr>
                                            <td class="text-muted">Catatan</td>
                                            <td>{{ $subscription->notes }}</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card card-flush">
                        <div class="card-header"><h3 class="card-title">Aksi</h3></div>
                        <div class="card-body pt-0 d-flex flex-column gap-2">

                            @if ($subscription->status !== 'paid')
                                <form method="POST" action="{{ route('subscriptions.paid', $subscription) }}"
                                    data-confirm="Tandai lunas dan aktifkan acaranya?">
                                    @csrf
                                    <button class="btn btn-success w-100">
                                        <i class="ki-outline ki-check fs-4"></i> Tandai Lunas
                                    </button>
                                </form>
                            @endif

                            <a href="{{ route('subscriptions.edit', $subscription) }}" class="btn btn-light-warning w-100">
                                Ubah pesanan
                            </a>

                            @if ($subscription->status !== 'cancelled')
                                <form method="POST" action="{{ route('subscriptions.cancel', $subscription) }}"
                                    data-confirm="Batalkan pesanan ini? Kamera acara akan dijeda.">
                                    @csrf
                                    <button class="btn btn-light w-100">Batalkan pesanan</button>
                                </form>
                            @endif

                            <form method="POST" action="{{ route('subscriptions.destroy', $subscription) }}"
                                data-confirm="Hapus pesanan {{ $subscription->invoice_number }}?">
                                @csrf @method('DELETE')
                                <button class="btn btn-light-danger w-100">Hapus pesanan</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
