<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Event;
use App\Models\EventGuest;
use App\Models\EventMedia;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Acara contoh yang benar-benar bisa dibuka: portal, kamera, dan galeri
 * berisi jepretan. Ada dua jenis (pernikahan dan wisuda) supaya terlihat
 * bahwa satu sistem ini melayani acara apa pun yang butuh banyak
 * dokumentasi dari tamunya.
 *
 * Gambarnya dibuat sendiri dengan GD, jadi repo tidak perlu menyimpan
 * berkas biner.
 */
class DemoEventSeeder extends Seeder
{
    public function run(): void
    {
        $signature = Plan::where('slug', 'signature')->first() ?? Plan::first();
        $grand = Plan::where('slug', 'grand')->first() ?? $signature;

        if (! $signature) {
            $this->command->warn('Jalankan PlanSeeder dulu.');

            return;
        }

        $this->seedEvent([
            'client' => [
                'email' => 'suci.rendy@example.com',
                'name' => 'Suci Rahmawati',
                'phone' => '081234567890',
                'instagram' => '@suci.rendy',
                'city' => 'Bandung',
            ],
            'plan' => $signature,
            'event' => [
                'slug' => 'suci-rendy',
                'title' => 'Pernikahan Suci & Rendy',
                'event_type' => 'wedding',
                'bride_name' => 'Suci',
                'groom_name' => 'Rendy',
                'hashtag' => '#SuciRendyForever',
                'venue' => 'Padma Hotel Ballroom',
                'city' => 'Bandung',
                'address' => 'Jl. Ranca Bentang No. 56-58, Bandung',
                'welcome_message' => 'Terima kasih sudah datang. Malam ini kamu fotografer kami.',
                'sign_message' => 'Kami tidak menyediakan fotografer. Ambil 18 foto sepanjang malam Anda!',
                'film_preset' => 'classic',
                'days_ahead' => 14,
            ],
            'guests' => ['Ayu', 'Rizky', 'Budi', 'Sari', 'Fajar', 'Dian', 'Nadia', 'Bagas'],
        ]);

        $this->seedEvent([
            'client' => [
                'email' => 'humas@univ-example.ac.id',
                'name' => 'Humas Universitas Cendana',
                'phone' => '022555111',
                'city' => 'Bandung',
            ],
            'plan' => $grand,
            'event' => [
                'slug' => 'wisuda-cendana-2026',
                'title' => 'Wisuda Universitas Cendana 2026',
                'event_type' => 'graduation',
                'host_name' => 'Universitas Cendana',
                'hashtag' => '#WisudaCendana2026',
                'venue' => 'Graha Sanusi',
                'city' => 'Bandung',
                'welcome_message' => 'Selamat, wisudawan! Abadikan hari ini bareng keluarga dan temanmu.',
                'sign_message' => 'Scan QR ini, kamu dapat 24 foto untuk merekam hari kelulusanmu.',
                'film_preset' => 'amber',
                'days_ahead' => 30,
            ],
            'guests' => ['Wulan', 'Pras', 'Intan', 'Yoga', 'Mira'],
        ]);
    }

    /** @param array<string,mixed> $spec */
    private function seedEvent(array $spec): void
    {
        /** @var Plan $plan */
        $plan = $spec['plan'];

        $client = Client::updateOrCreate(
            ['email' => $spec['client']['email']],
            $spec['client'] + ['notes' => 'Klien contoh bawaan sistem.']
        );

        $details = $spec['event'];
        $daysAhead = $details['days_ahead'];
        unset($details['days_ahead']);

        $event = Event::updateOrCreate(
            ['slug' => $details['slug']],
            $details + [
                'client_id' => $client->id,
                'plan_id' => $plan->id,
                // Seeder berjalan dengan model event dimatikan
                // (WithoutModelEvents), jadi token QR diisi eksplisit.
                'qr_token' => Str::lower(Str::random(24)),
                'event_date' => now()->addDays($daysAhead)->toDateString(),
                'photo_quota' => $plan->photo_quota,
                'video_quota' => $plan->video_quota,
                'video_duration' => $plan->video_duration,
                'boomerang_quota' => $plan->boomerang_quota,
                'max_guests' => $plan->max_guests,
                'gallery_reveal' => 'instant',
                'gallery_public' => true,
                'require_guest_name' => true,
                'allow_download' => true,
                'moderation' => false,
                'status' => 'active',
                'media_expires_at' => now()->addDays($plan->storage_days),
            ]
        );

        Subscription::updateOrCreate(
            ['event_id' => $event->id],
            [
                'invoice_number' => 'INV/' . now()->format('Y/m') . '/' . str_pad((string) $event->id[0], 4, '0', STR_PAD_LEFT) . Str::upper(Str::random(3)),
                'client_id' => $client->id,
                'plan_id' => $plan->id,
                'amount' => $plan->price,
                'discount' => 0,
                'total' => $plan->price,
                'status' => 'paid',
                'payment_method' => 'transfer',
                'paid_at' => now()->subDays(3),
                'starts_at' => $event->event_date,
                'ends_at' => now()->addDays($plan->storage_days),
            ]
        );

        // Ulang dari bersih supaya seeder aman dijalankan berkali-kali.
        foreach ($event->media as $old) {
            $old->deleteFiles();
            $old->forceDelete();
        }

        $event->guests()->delete();

        $created = 0;

        foreach ($spec['guests'] as $index => $name) {
            $guest = EventGuest::create([
                'event_id' => $event->id,
                'name' => $name,
                'token' => Str::random(48),
                'color' => EventGuest::COLORS[$index % count(EventGuest::COLORS)],
                'photo_quota' => $event->photo_quota,
                'video_quota' => $event->video_quota,
                'boomerang_quota' => $event->boomerang_quota,
                'last_active_at' => now()->subMinutes(random_int(5, 600)),
            ]);

            foreach (range(1, random_int(2, 4)) as $ignored) {
                $this->makePhoto($event, $guest, $created++);
            }
        }

        $this->command->info("Acara contoh siap: {$event->title}");
        $this->command->line('  Portal : ' . url('/' . $event->slug));
        $this->command->line('  Kamera : ' . url('/' . $event->slug . '/kamera?k=' . $event->qr_token));
        $this->command->line("  Media  : {$created} foto dari " . count($spec['guests']) . ' tamu');
    }

    /** Foto contoh diambil dari pustaka di public/assets/media/showcase. */
    private function library(): array
    {
        static $files = null;

        return $files ??= glob(public_path('assets/media/showcase/*.jpg')) ?: [];
    }

    /** Salin satu foto pustaka ke penyimpanan acara dan catat di database. */
    private function makePhoto(Event $event, EventGuest $guest, int $index): void
    {
        $library = $this->library();

        if ($library === []) {
            $this->command->warn('Pustaka foto contoh kosong: public/assets/media/showcase.');

            return;
        }

        $image = imagecreatefromjpeg($library[$index % count($library)]);
        $width = imagesx($image);
        $height = imagesy($image);

        $folder = 'events/' . $event->slug . '/photo';
        $filename = Str::uuid()->toString() . '.jpg';
        $relative = $folder . '/' . $filename;

        $disk = Storage::disk('public');
        $absolute = $disk->path($relative);

        if (! is_dir(dirname($absolute))) {
            mkdir(dirname($absolute), 0o755, true);
        }

        imagejpeg($image, $absolute, 86);

        // Thumbnail, sama seperti yang dibuat CaptureService.
        $thumbRelative = $folder . '/thumb/' . pathinfo($filename, PATHINFO_FILENAME) . '.jpg';
        $thumbAbsolute = $disk->path($thumbRelative);

        if (! is_dir(dirname($thumbAbsolute))) {
            mkdir(dirname($thumbAbsolute), 0o755, true);
        }

        $thumbWidth = 540;
        $thumbHeight = (int) round($height * ($thumbWidth / $width));
        $thumb = imagecreatetruecolor($thumbWidth, $thumbHeight);
        imagecopyresampled($thumb, $image, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height);
        imagejpeg($thumb, $thumbAbsolute, 82);
        imagedestroy($thumb);
        imagedestroy($image);

        EventMedia::create([
            'event_id' => $event->id,
            'event_guest_id' => $guest->id,
            'type' => 'photo',
            'disk' => 'public',
            'path' => $relative,
            'thumb_path' => $thumbRelative,
            'mime' => 'image/jpeg',
            'size' => (int) filesize($absolute),
            'width' => $width,
            'height' => $height,
            'film_preset' => $event->film_preset,
            'status' => 'approved',
            'captured_at' => now()->subMinutes(random_int(10, 720)),
        ]);

        $guest->increment('photos_used');
    }

}
