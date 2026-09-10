<div class="d-flex justify-content-end gap-1">
    <a href="{{ route('clients.show', $client) }}" class="btn btn-icon btn-sm btn-light" title="Detail">
        <i class="ki-outline ki-eye fs-4"></i>
    </a>
    <a href="{{ route('clients.edit', $client) }}" class="btn btn-icon btn-sm btn-light-warning" title="Ubah">
        <i class="ki-outline ki-pencil fs-4"></i>
    </a>
    <form method="POST" action="{{ route('clients.destroy', $client) }}" data-confirm="Hapus klien {{ $client->name }}?">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-icon btn-sm btn-light-danger" title="Hapus">
            <i class="ki-outline ki-trash fs-4"></i>
        </button>
    </form>
</div>
