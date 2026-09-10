<div class="d-flex flex-column">
    <a href="{{ route('subscriptions.show', $sub) }}" class="text-gray-900 fw-bold text-hover-primary">
        {{ $sub->invoice_number }}
    </a>
    <span class="text-muted fs-8">
        {{ $sub->client?->name }}
        @if ($sub->event) · {{ $sub->event->title }} @endif
    </span>
</div>
