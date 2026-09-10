@extends('backend.layout.app')
@section('title', $subscription->exists ? 'Ubah Pesanan' : 'Pesanan Baru')

@section('content')

    @include('backend.events._header', [
        'heading' => $subscription->exists ? 'Ubah Pesanan' : 'Pesanan Baru',
        'crumbs' => ['Manajemen Acara', 'Pesanan', $subscription->exists ? $subscription->invoice_number : 'Buat Baru'],
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

            <form method="POST"
                action="{{ $subscription->exists ? route('subscriptions.update', $subscription) : route('subscriptions.store') }}">
                @csrf
                @if ($subscription->exists)
                    @method('PUT')
                @endif

                <div class="card card-flush" style="max-width:960px">
                    <div class="card-header"><h3 class="card-title">Detail Pesanan</h3></div>
                    <div class="card-body pt-0">
                        <div class="row g-4">
                            <div class="col-md-4">
                                <label class="form-label required">Klien</label>
                                <select name="client_id" class="form-select form-select-solid" required>
                                    <option value="">— pilih klien —</option>
                                    @foreach ($clients as $client)
                                        <option value="{{ $client->id }}"
                                            @selected(old('client_id', $subscription->client_id) == $client->id)>
                                            {{ $client->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Paket</label>
                                <select name="plan_id" id="sub-plan" class="form-select form-select-solid">
                                    <option value="">— tanpa paket —</option>
                                    @foreach ($plans as $plan)
                                        <option value="{{ $plan->id }}" data-price="{{ (int) $plan->price }}"
                                            @selected(old('plan_id', $subscription->plan_id) == $plan->id)>
                                            {{ $plan->name }} — {{ $plan->price_label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Acara</label>
                                <select name="event_id" class="form-select form-select-solid">
                                    <option value="">— belum ditautkan —</option>
                                    @foreach ($events as $event)
                                        <option value="{{ $event->id }}"
                                            @selected(old('event_id', $subscription->event_id) == $event->id)>
                                            {{ $event->title }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Melunasi pesanan akan mengaktifkan acara ini.</div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label required">Nominal (Rp)</label>
                                <input type="number" name="amount" id="sub-amount" min="0" step="1000" required
                                    class="form-control form-control-solid"
                                    value="{{ old('amount', (int) $subscription->amount) }}" />
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Diskon (Rp)</label>
                                <input type="number" name="discount" min="0" step="1000"
                                    class="form-control form-control-solid"
                                    value="{{ old('discount', (int) $subscription->discount) }}" />
                            </div>

                            <div class="col-md-4">
                                <label class="form-label required">Status</label>
                                <select name="status" class="form-select form-select-solid" required>
                                    @foreach (\App\Models\Subscription::STATUSES as $status)
                                        <option value="{{ $status }}"
                                            @selected(old('status', $subscription->status) === $status)>
                                            {{ ucfirst($status) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Metode bayar</label>
                                <input type="text" name="payment_method" class="form-control form-control-solid"
                                    value="{{ old('payment_method', $subscription->payment_method) }}"
                                    placeholder="transfer / qris / tunai" />
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Referensi pembayaran</label>
                                <input type="text" name="payment_reference" class="form-control form-control-solid"
                                    value="{{ old('payment_reference', $subscription->payment_reference) }}" />
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Jatuh tempo</label>
                                <input type="date" name="due_at" class="form-control form-control-solid"
                                    value="{{ old('due_at', $subscription->due_at?->toDateString()) }}" />
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Mulai berlaku</label>
                                <input type="date" name="starts_at" class="form-control form-control-solid"
                                    value="{{ old('starts_at', $subscription->starts_at?->toDateString()) }}" />
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Berakhir</label>
                                <input type="date" name="ends_at" class="form-control form-control-solid"
                                    value="{{ old('ends_at', $subscription->ends_at?->toDateString()) }}" />
                            </div>

                            <div class="col-12">
                                <label class="form-label">Catatan</label>
                                <textarea name="notes" rows="3"
                                    class="form-control form-control-solid">{{ old('notes', $subscription->notes) }}</textarea>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-5">
                            <button type="submit" class="btn btn-primary">
                                {{ $subscription->exists ? 'Simpan Perubahan' : 'Buat Pesanan' }}
                            </button>
                            <a href="{{ route('subscriptions.index') }}" class="btn btn-light">Batal</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        // Memilih paket mengisi nominalnya, tetap boleh diubah manual.
        document.getElementById('sub-plan').addEventListener('change', function () {
            var price = this.options[this.selectedIndex].dataset.price;

            if (price) {
                document.getElementById('sub-amount').value = price;
            }
        });
    </script>
@endpush
