<div class="d-flex justify-content-end gap-1">
    @if ($sub->status === 'pending')
        <form method="POST" action="{{ route('subscriptions.paid', $sub) }}" data-confirm="Tandai {{ $sub->invoice_number }} lunas dan aktifkan acaranya?">
            @csrf
            <button type="submit" class="btn btn-sm btn-light-success" title="Tandai lunas">
                <i class="ki-outline ki-check fs-4"></i> Lunas
            </button>
        </form>
    @endif
    <a href="{{ route('subscriptions.show', $sub) }}" class="btn btn-icon btn-sm btn-light" title="Detail">
        <i class="ki-outline ki-eye fs-4"></i>
    </a>
    <a href="{{ route('subscriptions.edit', $sub) }}" class="btn btn-icon btn-sm btn-light-warning" title="Ubah">
        <i class="ki-outline ki-pencil fs-4"></i>
    </a>
</div>
