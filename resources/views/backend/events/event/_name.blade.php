<div class="d-flex flex-column">
    <a href="{{ route('events.show', $event) }}" class="text-gray-900 fw-bold text-hover-primary">
        {{ $event->title }}
    </a>
    <span class="text-muted fs-8">
        {{ $event->client?->name }} · <span class="text-primary">/{{ $event->slug }}</span>
        @if ($event->plan) · {{ $event->plan->name }} @endif
    </span>
</div>
