<?php

namespace App\Http\Controllers\Backend\Events;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Event;
use App\Models\Plan;
use App\Models\Subscription;
use App\Support\ActivityRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

/**
 * Pesanan paket. Melunasi pesanan otomatis membuka acaranya.
 */
class SubscriptionController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['auth', 'can:view_resources'];
    }

    public function index(): View
    {
        return view('backend.events.subscription.index', [
            'stats' => [
                'pending' => Subscription::where('status', 'pending')->count(),
                'paid' => Subscription::where('status', 'paid')->count(),
                'revenue' => (float) Subscription::where('status', 'paid')->sum('total'),
            ],
        ]);
    }

    public function getData(Request $request): JsonResponse
    {
        $query = Subscription::with('client:id,name', 'plan:id,name', 'event:id,slug,title')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->latest();

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('invoice', fn (Subscription $sub) => view('backend.events.subscription._name', compact('sub'))->render())
            ->addColumn('plan_name', fn (Subscription $sub) => e($sub->plan?->name ?? '-'))
            ->addColumn('total_label', fn (Subscription $sub) => $sub->total_label)
            ->addColumn('status', fn (Subscription $sub) => '<span class="badge badge-light-' . $sub->status_badge . '">' . $sub->status_label . '</span>')
            ->addColumn('action', fn (Subscription $sub) => view('backend.events.subscription._action', compact('sub'))->render())
            ->rawColumns(['invoice', 'status', 'action'])
            ->make(true);
    }

    public function create(): View
    {
        return view('backend.events.subscription.form', [
            'subscription' => new Subscription(['status' => 'pending']),
            'clients' => Client::orderBy('name')->get(),
            'plans' => Plan::orderBy('sort_order')->get(),
            'events' => Event::orderBy('title')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['invoice_number'] = Subscription::nextInvoiceNumber();

        $subscription = Subscription::create($data);
        $this->syncEventState($subscription);

        ActivityRecorder::created('tambah pesanan', 'Membuat pesanan ' . $subscription->invoice_number, $subscription->toArray(), $subscription);

        return redirect()->route('subscriptions.index')->with('success', 'Pesanan ' . $subscription->invoice_number . ' dibuat.');
    }

    public function show(Subscription $subscription): View
    {
        $subscription->load('client', 'plan', 'event');

        return view('backend.events.subscription.show', compact('subscription'));
    }

    public function edit(Subscription $subscription): View
    {
        return view('backend.events.subscription.form', [
            'subscription' => $subscription,
            'clients' => Client::orderBy('name')->get(),
            'plans' => Plan::orderBy('sort_order')->get(),
            'events' => Event::orderBy('title')->get(),
        ]);
    }

    public function update(Request $request, Subscription $subscription): RedirectResponse
    {
        $old = $subscription->toArray();
        $subscription->update($this->validated($request));
        $this->syncEventState($subscription);

        ActivityRecorder::updated('edit pesanan', 'Mengubah pesanan ' . $subscription->invoice_number, $old, $subscription->toArray(), $subscription);

        return redirect()->route('subscriptions.show', $subscription)->with('success', 'Pesanan diperbarui.');
    }

    /** Tandai lunas dan buka acaranya. */
    public function markPaid(Request $request, Subscription $subscription): RedirectResponse
    {
        $data = $request->validate([
            'payment_method' => ['nullable', 'string', 'max:60'],
            'payment_reference' => ['nullable', 'string', 'max:120'],
        ]);

        $subscription->update([
            'status' => 'paid',
            'paid_at' => now(),
            'payment_method' => $data['payment_method'] ?? $subscription->payment_method ?? 'transfer',
            'payment_reference' => $data['payment_reference'] ?? $subscription->payment_reference,
        ]);

        $this->syncEventState($subscription);

        ActivityRecorder::updated(
            'lunasi pesanan',
            'Menandai lunas pesanan ' . $subscription->invoice_number,
            ['status' => 'pending'],
            ['status' => 'paid'],
            $subscription
        );

        return back()->with('success', 'Pesanan lunas. Acara sudah diaktifkan.');
    }

    public function cancel(Subscription $subscription): RedirectResponse
    {
        $subscription->update(['status' => 'cancelled']);

        if ($subscription->event && $subscription->event->status === 'active') {
            $subscription->event->update(['status' => 'paused']);
        }

        return back()->with('success', 'Pesanan dibatalkan dan kamera acara dijeda.');
    }

    public function destroy(Subscription $subscription): RedirectResponse
    {
        $snapshot = $subscription->toArray();
        $subscription->delete();

        ActivityRecorder::deleted('hapus pesanan', 'Menghapus pesanan ' . $snapshot['invoice_number'], $snapshot);

        return redirect()->route('subscriptions.index')->with('success', 'Pesanan dihapus.');
    }

    // ------------------------------------------------------------------

    /** Pesanan lunas membuka acaranya; selain itu acara tetap draft. */
    private function syncEventState(Subscription $subscription): void
    {
        $event = $subscription->event;

        if (! $event) {
            return;
        }

        if ($subscription->status === 'paid' && $event->status === 'draft') {
            $event->update(['status' => 'active']);
        }
    }

    /** @return array<string,mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'plan_id' => ['nullable', 'exists:plans,id'],
            'event_id' => ['nullable', 'exists:events,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(Subscription::STATUSES)],
            'payment_method' => ['nullable', 'string', 'max:60'],
            'payment_reference' => ['nullable', 'string', 'max:120'],
            'due_at' => ['nullable', 'date'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $data['discount'] = $data['discount'] ?? 0;
        $data['total'] = max(0, $data['amount'] - $data['discount']);
        $data['paid_at'] = $data['status'] === 'paid' ? now() : null;

        return $data;
    }
}
