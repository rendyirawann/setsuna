<?php

namespace App\Http\Controllers\Backend\Events;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Support\ActivityRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

/**
 * Paket langganan yang ditawarkan di halaman publik.
 */
class PlanController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['auth', 'can:view_resources'];
    }

    public function index(): View
    {
        return view('backend.events.plan.index');
    }

    public function getData(Request $request): JsonResponse
    {
        $query = Plan::withCount('events')->orderBy('sort_order')->orderBy('price');

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('plan', fn (Plan $plan) => view('backend.events.plan._name', compact('plan'))->render())
            ->addColumn('price_label', fn (Plan $plan) => $plan->price_label)
            ->addColumn('quota', fn (Plan $plan) => $plan->photo_quota . ' foto · ' . $plan->video_quota . ' video · ' . $plan->boomerang_quota . ' boomerang')
            ->addColumn('status', fn (Plan $plan) => $plan->is_active
                ? '<span class="badge badge-light-success">Aktif</span>'
                : '<span class="badge badge-light-secondary">Nonaktif</span>')
            ->addColumn('action', fn (Plan $plan) => view('backend.events.plan._action', compact('plan'))->render())
            ->rawColumns(['plan', 'status', 'action'])
            ->make(true);
    }

    public function create(): View
    {
        return view('backend.events.plan.form', [
            'plan' => new Plan(config('setsuna.defaults') + ['is_active' => true, 'storage_days' => 90]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $plan = Plan::create($data);

        ActivityRecorder::created('tambah paket', 'Membuat paket ' . $plan->name, $plan->toArray(), $plan);

        return redirect()->route('plans.index')->with('success', 'Paket ' . $plan->name . ' berhasil dibuat.');
    }

    public function edit(Plan $plan): View
    {
        return view('backend.events.plan.form', compact('plan'));
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $old = $plan->toArray();
        $plan->update($this->validated($request, $plan));

        ActivityRecorder::updated('edit paket', 'Mengubah paket ' . $plan->name, $old, $plan->toArray(), $plan);

        return redirect()->route('plans.index')->with('success', 'Paket ' . $plan->name . ' berhasil diperbarui.');
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        if ($plan->events()->exists()) {
            return back()->with('error', 'Paket ini sudah dipakai acara, nonaktifkan saja agar riwayat tetap utuh.');
        }

        $snapshot = $plan->toArray();
        $plan->delete();

        ActivityRecorder::deleted('hapus paket', 'Menghapus paket ' . $snapshot['name'], $snapshot);

        return redirect()->route('plans.index')->with('success', 'Paket berhasil dihapus.');
    }

    /** @return array<string,mixed> */
    private function validated(Request $request, ?Plan $plan = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9][a-z0-9\-]*$/', 'unique:plans,slug' . ($plan ? ',' . $plan->id : '')],
            'tagline' => ['nullable', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'min:0'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'max_guests' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'photo_quota' => ['required', 'integer', 'min:1', 'max:500'],
            'video_quota' => ['required', 'integer', 'min:0', 'max:100'],
            'video_duration' => ['required', 'integer', 'min:3', 'max:60'],
            'boomerang_quota' => ['required', 'integer', 'min:0', 'max:100'],
            'storage_days' => ['required', 'integer', 'min:7', 'max:3650'],
            'features' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $data['features'] = array_values(array_filter(array_map(
            'trim',
            preg_split('/\r\n|\r|\n/', (string) $request->input('features'))
        )));
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }
}
