{{-- Satu ubin di mosaik galeri. Filter film diterapkan saat tampil agar
     video pun terlihat seragam dengan foto yang filternya sudah dibakar. --}}
@php
    $presetCss = \App\Support\FilmPresets::css($item->film_preset ?: $event->film_preset);
@endphp

<div class="tile reveal" data-media-id="{{ $item->id }}" style="filter:{{ $item->type === 'photo' ? 'none' : $presetCss }}">
    <button type="button" class="tile__open" data-lightbox
        data-type="{{ $item->type }}"
        data-src="{{ $item->url() }}"
        data-preview="{{ $item->previewUrl() }}"
        data-by="{{ $item->guest?->name ?? 'Tamu' }}"
        data-preset="{{ $presetCss }}"
        data-download="{{ $event->allow_download ? $item->downloadUrl() : '' }}">

        <img src="{{ $item->previewUrl() }}" alt="Jepretan {{ $item->guest?->name }}" loading="lazy" />

        @if ($item->type !== 'photo')
            <span class="tile__type">{{ $item->type === 'video' ? '▶ video' : '∞ boomerang' }}</span>
        @endif

        @if ($item->guest)
            <span class="tile__by"><i></i>{{ $item->guest->name }}</span>
        @endif
    </button>

    @if ($event->allow_download)
        <a class="tile__dl" href="{{ $item->downloadUrl() }}" download
            aria-label="Unduh jepretan {{ $item->guest?->name }}">
            @include('partials.line-icon', ['name' => 'download'])
        </a>
    @endif
</div>
