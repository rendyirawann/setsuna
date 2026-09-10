<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Event;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Pemesanan paket dari sisi publik.
 *
 * Pesanan masuk sebagai subscription "pending" beserta event "draft".
 * Admin yang memverifikasi pembayaran lalu mengaktifkan acaranya, jadi
 * tidak ada portal yang hidup sebelum dibayar.
 */
class OrderController extends Controller
{
    public function create(Plan $plan): View
    {
        abort_unless($plan->is_active, 404);

        return view('frontend.order', [
            'plan' => $plan,
            'plans' => Plan::active()->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request, Plan $plan): RedirectResponse
    {
        abort_unless($plan->is_active, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160'],
            'phone' => ['required', 'string', 'max:30'],
            'instagram' => ['nullable', 'string', 'max:60'],
            'bride_name' => ['required', 'string', 'max:80'],
            'groom_name' => ['required', 'string', 'max:80'],
            'event_date' => ['required', 'date', 'after_or_equal:today'],
            'venue' => ['nullable', 'string', 'max:160'],
            'city' => ['nullable', 'string', 'max:80'],
            'slug' => ['nullable', 'string', 'max:60', 'regex:/^[a-z0-9][a-z0-9\-]*$/'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'slug.regex' => 'Alamat portal hanya boleh huruf kecil, angka, dan tanda hubung.',
        ]);

        $subscription = DB::transaction(function () use ($data, $plan) {
            $client = Client::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'instagram' => $data['instagram'] ?? null,
                    'city' => $data['city'] ?? null,
                ]
            );

            $event = Event::create([
                'client_id' => $client->id,
                'plan_id' => $plan->id,
                'slug' => $this->uniqueSlug($data['slug'] ?? ($data['bride_name'] . '-' . $data['groom_name'])),
                'title' => 'Pernikahan ' . $data['bride_name'] . ' & ' . $data['groom_name'],
                'bride_name' => $data['bride_name'],
                'groom_name' => $data['groom_name'],
                'event_date' => $data['event_date'],
                'venue' => $data['venue'] ?? null,
                'city' => $data['city'] ?? null,
                'photo_quota' => $plan->photo_quota,
                'video_quota' => $plan->video_quota,
                'video_duration' => $plan->video_duration,
                'boomerang_quota' => $plan->boomerang_quota,
                'max_guests' => $plan->max_guests,
                'media_expires_at' => now()->addDays($plan->storage_days),
                'status' => 'draft',
            ]);

            return Subscription::create([
                'invoice_number' => Subscription::nextInvoiceNumber(),
                'client_id' => $client->id,
                'plan_id' => $plan->id,
                'event_id' => $event->id,
                'amount' => $plan->price,
                'discount' => 0,
                'total' => $plan->price,
                'currency' => $plan->currency,
                'status' => 'pending',
                'due_at' => now()->addDays(3),
                'starts_at' => $data['event_date'],
                'ends_at' => now()->parse($data['event_date'])->addDays($plan->storage_days),
                'notes' => $data['notes'] ?? null,
            ]);
        });

        session()->put('order.' . $subscription->id, true);

        return redirect()->route('order.success', $subscription->id);
    }

    public function success(Request $request, Subscription $subscription): View
    {
        abort_unless($request->session()->get('order.' . $subscription->id), 403);

        $subscription->load(['plan', 'client', 'event']);

        return view('frontend.order-success', compact('subscription'));
    }

    /** Slug portal harus unik karena dipakai sebagai alamat publik. */
    private function uniqueSlug(string $source): string
    {
        $base = Str::slug($source) ?: 'acara';
        $slug = $base;
        $suffix = 2;

        while (Event::withTrashed()->where('slug', $slug)->exists() || in_array($slug, config('setsuna.reserved_slugs'), true)) {
            $slug = $base . '-' . $suffix++;
        }

        return $slug;
    }
}
