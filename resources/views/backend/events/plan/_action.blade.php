<div class="d-flex justify-content-end gap-1">
    <a href="{{ route('plans.edit', $plan) }}" class="btn btn-icon btn-sm btn-light-warning" title="Ubah">
        <i class="ki-outline ki-pencil fs-4"></i>
    </a>
    <form method="POST" action="{{ route('plans.destroy', $plan) }}" data-confirm="Hapus paket {{ $plan->name }}?">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-icon btn-sm btn-light-danger" title="Hapus">
            <i class="ki-outline ki-trash fs-4"></i>
        </button>
    </form>
</div>
