{{-- Ikon garis tipis, satu berkas untuk semua halaman publik.
     Pakai: @include('partials.line-icon', ['name' => 'rings']) --}}
@php
    $paths = [
        // Dua cincin bertaut — pernikahan.
        'rings' => '<circle cx="9" cy="15" r="6"/><circle cx="15" cy="15" r="6"/><path d="M9 9V4.5M15 9V4.5M7.5 4.5h3M13.5 4.5h3"/>',
        // Topi toga — wisuda.
        'cap' => '<path d="M2 8.5 12 4l10 4.5-10 4.5L2 8.5Z"/><path d="M6 10.6V16c0 1.7 2.7 3 6 3s6-1.3 6-3v-5.4"/><path d="M21 9v5"/>',
        // Not balok — prom dan pensi.
        'note' => '<path d="M9 18V5l11-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="17" cy="16" r="3"/>',
        // Gedung — acara kantor.
        'building' => '<path d="M3 21h18"/><path d="M5 21V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v16"/><path d="M15 21V10h3a2 2 0 0 1 2 2v9"/><path d="M9 7h2M9 11h2M9 15h2"/>',
        // Kue — ulang tahun.
        'cake' => '<path d="M4 21h16v-6a3 3 0 0 0-3-3H7a3 3 0 0 0-3 3v6Z"/><path d="M4 17c1.5 0 1.5 1.4 3 1.4S8.5 17 10 17s1.5 1.4 3 1.4S14.5 17 16 17s1.5 1.4 3 1.4"/><path d="M8 12V8M12 12V7M16 12V8"/><path d="M8 6V5M12 5V4M16 6V5"/>',
        // Lampion — festival.
        'lantern' => '<path d="M12 2v2M12 20v2"/><ellipse cx="12" cy="12" rx="6" ry="8"/><path d="M8 6h8M8 18h8"/><path d="M12 4c-2.5 2.4-2.5 13.6 0 16M12 4c2.5 2.4 2.5 13.6 0 16"/>',
        // Tautan — portal khusus.
        'link' => '<path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7"/><path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7L12 19"/>',
        // Kamera — mode kamera tamu.
        'camera' => '<path d="M3 8.5A2.5 2.5 0 0 1 5.5 6h1.7a2 2 0 0 0 1.7-.9l.8-1.2a2 2 0 0 1 1.7-.9h1.2a2 2 0 0 1 1.7.9l.8 1.2a2 2 0 0 0 1.7.9h1.7A2.5 2.5 0 0 1 21 8.5v9A2.5 2.5 0 0 1 18.5 20h-13A2.5 2.5 0 0 1 3 17.5v-9Z"/><circle cx="12" cy="13" r="4"/>',
        // Printer — papan QR siap cetak.
        'printer' => '<path d="M7 9V3h10v6"/><path d="M7 19H5a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M7 15h10v6H7z"/>',
        // Unduh — arsip zip.
        'download' => '<path d="M12 3v12"/><path d="m7 11 5 5 5-5"/><path d="M4 20h16"/>',
        // GitHub.
        'github' => '<path d="M9 19c-4.3 1.3-4.3-2.2-6-2.7m12 5.7v-3.9a3.4 3.4 0 0 0-.9-2.6c3-.3 6.2-1.5 6.2-6.7A5.2 5.2 0 0 0 19 5.3a4.9 4.9 0 0 0-.1-3.6s-1.1-.4-3.7 1.4a12.6 12.6 0 0 0-6.6 0C6 1.3 4.9 1.7 4.9 1.7a4.9 4.9 0 0 0-.1 3.6A5.2 5.2 0 0 0 3.4 8.9c0 5.2 3.2 6.4 6.2 6.7a3.4 3.4 0 0 0-.9 2.6V22"/>',
        // LinkedIn.
        'linkedin' => '<path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-4 0v7h-4V9h4v1.5A6 6 0 0 1 16 8Z"/><rect x="2" y="9" width="4" height="12" rx="1"/><circle cx="4" cy="4" r="2"/>',
    ];
@endphp

<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor"
    stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    {!! $paths[$name] ?? $paths['camera'] !!}
</svg>
