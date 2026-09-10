{{-- Satu ubin di mosaik galeri. Filter film diterapkan saat tampil agar
     video pun terlihat seragam dengan foto yang filternya sudah dibakar. --}}
@php
    $presetCss = \App\Support\FilmPresets::css($item->film_preset ?: $event->film_preset);
@endphp

<button type="button" class="tile reveal" data-lightbox
    data-type="{{ $item->type }}"
    data-src="{{ $item->url() }}"
    data-preview="{{ $item->previewUrl() }}"
    data-by="{{ $item->guest?->name ?? 'Tamu' }}"
    data-preset="{{ $presetCss }}"
    data-download="{{ $event->allow_download ? $item->downloadUrl() : '' }}"
    style="border:0;padding:0;width:100%;cursor:zoom-in;filter:{{ $item->type === 'photo' ? 'none' : $presetCss }}">

    <img src="{{ $item->previewUrl() }}" alt="Jepretan {{ $item->guest?->name }}" loading="lazy" />

    @if ($item->type !== 'photo')
        <span class="tile__type">{{ $item->type === 'video' ? '▶ video' : '∞ boomerang' }}</span>
    @endif

    @if ($item->guest)
        <span class="tile__by"><i></i>{{ $item->guest->name }}</span>
    @endif
</button>
