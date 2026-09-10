<?php

namespace App\Http\Controllers\Backend\Events;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Support\ActivityRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

/**
 * Klien pemesan paket (mempelai, panitia, atau event organizer).
 */
class ClientController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['auth', 'can:view_resources'];
    }

    public function index(): View
    {
        return view('backend.events.client.index');
    }

    public function getData(Request $request): JsonResponse
    {
        $query = Client::withCount(['events', 'subscriptions'])->latest();

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('client', fn (Client $client) => view('backend.events.client._name', compact('client'))->render())
            ->addColumn('contact', fn (Client $client) => e($client->phone ?: '-') . '<br><span class="text-muted fs-8">' . e($client->email ?: '-') . '</span>')
            ->addColumn('action', fn (Client $client) => view('backend.events.client._action', compact('client'))->render())
            ->rawColumns(['client', 'contact', 'action'])
            ->make(true);
    }

    public function create(): View
    {
        return view('backend.events.client.form', ['client' => new Client()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $client = Client::create($this->validated($request));

        ActivityRecorder::created('tambah klien', 'Menambah klien ' . $client->name, $client->toArray(), $client);

        return redirect()->route('clients.index')->with('success', 'Klien ' . $client->name . ' berhasil ditambahkan.');
    }

    public function show(Client $client): View
    {
        $client->load(['events' => fn ($query) => $query->withCount(['guests', 'media'])->latest(), 'subscriptions.plan']);

        return view('backend.events.client.show', compact('client'));
    }

    public function edit(Client $client): View
    {
        return view('backend.events.client.form', compact('client'));
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $old = $client->toArray();
        $client->update($this->validated($request, $client));

        ActivityRecorder::updated('edit klien', 'Mengubah klien ' . $client->name, $old, $client->toArray(), $client);

        return redirect()->route('clients.index')->with('success', 'Data klien berhasil diperbarui.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        if ($client->events()->exists()) {
            return back()->with('error', 'Klien ini masih punya acara. Hapus acaranya lebih dulu.');
        }

        $snapshot = $client->toArray();
        $client->delete();

        ActivityRecorder::deleted('hapus klien', 'Menghapus klien ' . $snapshot['name'], $snapshot);

        return redirect()->route('clients.index')->with('success', 'Klien berhasil dihapus.');
    }

    /** @return array<string,mixed> */
    private function validated(Request $request, ?Client $client = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:160', 'unique:clients,email' . ($client ? ',' . $client->id : '')],
            'phone' => ['nullable', 'string', 'max:30'],
            'instagram' => ['nullable', 'string', 'max:60'],
            'city' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
