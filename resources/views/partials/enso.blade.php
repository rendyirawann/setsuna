{{-- Ensō: lingkaran sapuan kuas yang sengaja tidak menutup.
     Dipakai sebagai pemisah bagian, menggantikan garis biasa. --}}
<div class="enso-row">
    <svg class="enso" viewBox="0 0 64 64" aria-hidden="true">
        <defs>
            <linearGradient id="enso-{{ $id ?? 'a' }}" x1="0.1" y1="0" x2="0.9" y2="1">
                <stop offset="0" stop-color="#F4DFC0" />
                <stop offset="0.55" stop-color="#DDB681" />
                <stop offset="1" stop-color="#C7985E" />
            </linearGradient>
        </defs>
        <path fill="none" stroke="url(#enso-{{ $id ?? 'a' }})" stroke-width="2.6" stroke-linecap="round"
            d="M45.5 12.5a24 24 0 1 0 8.2 12.4" />
    </svg>
</div>
