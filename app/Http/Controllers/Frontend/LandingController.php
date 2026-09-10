<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventMedia;
use App\Models\Plan;
use App\Support\FilmPresets;
use Illuminate\View\View;

/**
 * Halaman publik: etalase produk sebelum klien memesan paket.
 */
class LandingController extends Controller
{
    public function index(): View
    {
        return view('frontend.home', [
            'plans' => $this->plans(),
            'presets' => FilmPresets::all(),
            'showcase' => $this->showcase(),
            'stats' => $this->stats(),
        ]);
    }

    public function pricing(): View
    {
        return view('frontend.pricing', [
            'plans' => $this->plans(),
        ]);
    }

    public function faq(): View
    {
        return view('frontend.faq');
    }

    /** @return \Illuminate\Support\Collection<int,Plan> */
    private function plans()
    {
        return Plan::active()->orderBy('sort_order')->orderBy('price')->get();
    }

    /**
     * Beberapa jepretan asli untuk mengisi mosaik di beranda. Hanya
     * diambil dari acara yang galerinya memang publik.
     */
    private function showcase()
    {
        return EventMedia::query()
            ->visible()
            ->where('type', 'photo')
            ->whereHas('event', fn ($query) => $query->where('gallery_public', true))
            ->latest()
            ->limit(18)
            ->get();
    }

    /** @return array<string,int> */
    private function stats(): array
    {
        return [
            'events' => Event::whereIn('status', ['active', 'completed', 'archived'])->count(),
            'guests' => \App\Models\EventGuest::count(),
            'captures' => EventMedia::count(),
        ];
    }
}
