@extends('portal.layout')

@section('title', 'Album ' . $event->couple)

@section('content')

    <section class="section" style="padding-top:clamp(40px,6vw,70px)">
        <div class="wrap">

            <div class="section__head center">
                <span class="kana">しゃしん · 写真</span>
                <span class="eyebrow">Album bersama</span>
                <h2>{{ $event->couple }}</h2>
                <p class="muted">
                    {{ number_format($counts['total'], 0, ',', '.') }} momen dari
                    {{ number_format($counts['guests'], 0, ',', '.') }} tamu.
                    Inilah acaramu dari sudut pandang mereka.
                </p>
            </div>

            {{-- ---------------------------------------------------- Saring --}}
            <div class="chips" style="justify-content:center;margin-bottom:26px">
                <a href="{{ $event->galleryUrl() }}" class="chip {{ $activeType ? '' : 'chip--on' }}">
                    Semua ({{ $counts['total'] }})
                </a>
                @foreach (['photo' => 'Foto', 'video' => 'Video', 'boomerang' => 'Boomerang'] as $type => $label)
                    @if ($counts[$type] > 0)
                        <a href="{{ $event->galleryUrl() }}?type={{ $type }}"
                            class="chip {{ $activeType === $type ? 'chip--on' : '' }}">
                            {{ $label }} ({{ $counts[$type] }})
                        </a>
                    @endif
                @endforeach
            </div>

            @if ($contributors->count())
                <div class="chips" style="justify-content:center;margin-bottom:36px">
                    @foreach ($contributors as $person)
                        <a href="{{ route('portal.guest', [$event->slug, $person->id]) }}" class="chip">
                            {{ $person->name }} · {{ $person->media_count }}
                        </a>
                    @endforeach
                </div>
            @endif

            {{-- ---------------------------------------------------- Mosaik --}}
            @if ($media->count())
                <div class="gallery-grid" data-live-grid="all">
                    @foreach ($media as $item)
                        @include('portal._tile', ['item' => $item, 'event' => $event])
                    @endforeach
                </div>

                <div class="pagination-wrap">{{ $media->links() }}</div>
            @else
                <div class="empty" data-live-empty>
                    <p style="margin:0">Belum ada jepretan di kategori ini. Halaman ini menyala sendiri begitu ada yang memotret.</p>
                </div>
            @endif
        </div>
    </section>

    @include('portal._lightbox')

@endsection
