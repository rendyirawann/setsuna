<div class="d-flex align-items-center">
    <div class="symbol symbol-circle symbol-40px me-3">
        <div class="symbol-label bg-light-primary text-primary fw-bold">{{ $client->initials }}</div>
    </div>
    <div class="d-flex flex-column">
        <a href="{{ route('clients.show', $client) }}" class="text-gray-900 fw-bold text-hover-primary">{{ $client->name }}</a>
        <span class="text-muted fs-8">{{ $client->events_count }} acara · {{ $client->subscriptions_count }} pesanan</span>
    </div>
</div>
