<div class="d-flex justify-content-end gap-1">
    <a href="{{ route('events.sign', $event) }}" class="btn btn-icon btn-sm btn-light-info" title="Papan QR" target="_blank">
        <i class="ki-outline ki-scan-barcode fs-4"></i>
    </a>
    <a href="{{ route('events.media', $event) }}" class="btn btn-icon btn-sm btn-light-primary" title="Media">
        <i class="ki-outline ki-picture fs-4"></i>
    </a>
    <a href="{{ route('events.show', $event) }}" class="btn btn-icon btn-sm btn-light" title="Detail">
        <i class="ki-outline ki-eye fs-4"></i>
    </a>
    <a href="{{ route('events.edit', $event) }}" class="btn btn-icon btn-sm btn-light-warning" title="Ubah">
        <i class="ki-outline ki-pencil fs-4"></i>
    </a>
</div>
