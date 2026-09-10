<div class="d-flex flex-column">
    <span class="text-gray-900 fw-bold">
        {{ $plan->name }}
        @if ($plan->is_featured)
            <span class="badge badge-light-warning ms-1">Unggulan</span>
        @endif
    </span>
    <span class="text-muted fs-8">{{ $plan->tagline }} · dipakai {{ $plan->events_count }} acara</span>
</div>
