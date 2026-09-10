{{-- Kartu satu paket, dipakai di beranda dan halaman harga. --}}
<div class="plan reveal {{ $plan->is_featured ? 'plan--featured' : '' }}">
    @if ($plan->is_featured)
        <span class="plan__badge">Paling dipilih</span>
    @endif

    <h3>{{ $plan->name }}</h3>
    <p class="tagline">{{ $plan->tagline }}</p>

    <div class="plan__price">{{ $plan->price_label }}</div>

    @if ($plan->compare_at_price_label)
        <div class="plan__was">{{ $plan->compare_at_price_label }}</div>
    @endif

    <div class="plan__note">Sekali bayar · satu acara</div>

    <ul>
        @foreach ($plan->features ?? [] as $feature)
            <li>{{ $feature }}</li>
        @endforeach
    </ul>

    <a href="{{ route('order.create', $plan->slug) }}"
        class="btn {{ $plan->is_featured ? 'btn--gold' : 'btn--ghost' }} btn--block">
        Pilih {{ $plan->name }}
    </a>
</div>
