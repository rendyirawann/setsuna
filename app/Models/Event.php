<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Acara pernikahan milik satu klien.
 *
 * Slug-nya adalah alamat portal publik (mis. /pernikahan-emma): satu
 * "sub folder" berisi kamera tamu dan galeri hasil jepretan acara itu.
 */
class Event extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public const STATUSES = ['draft', 'active', 'paused', 'completed', 'archived'];

    /** Jenis acara yang didukung, beserta labelnya di admin. */
    public const TYPES = [
        'wedding' => 'Pernikahan',
        'graduation' => 'Wisuda',
        'prom' => 'Prom / Pensi Sekolah',
        'corporate' => 'Acara Kantor / Gathering',
        'birthday' => 'Ulang Tahun',
        'other' => 'Acara Lainnya',
    ];

    protected $fillable = [
        'client_id',
        'plan_id',
        'slug',
        'title',
        'event_type',
        'bride_name',
        'groom_name',
        'host_name',
        'hashtag',
        'event_date',
        'venue',
        'city',
        'address',
        'cover_path',
        'welcome_message',
        'sign_message',
        'theme',
        'film_preset',
        'photo_quota',
        'video_quota',
        'video_duration',
        'boomerang_quota',
        'max_guests',
        'gallery_reveal',
        'gallery_public',
        'gallery_password',
        'gallery_unlocked',
        'require_guest_name',
        'allow_download',
        'moderation',
        'capture_opens_at',
        'capture_closes_at',
        'media_expires_at',
        'status',
        'qr_token',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'capture_opens_at' => 'datetime',
            'capture_closes_at' => 'datetime',
            'media_expires_at' => 'datetime',
            'gallery_public' => 'boolean',
            'gallery_unlocked' => 'boolean',
            'require_guest_name' => 'boolean',
            'allow_download' => 'boolean',
            'moderation' => 'boolean',
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Event $event) {
            $event->qr_token ??= Str::lower(Str::random(24));
            $event->slug ??= Str::slug($event->title);
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // ------------------------------------------------------------------
    // Relations
    // ------------------------------------------------------------------

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function guests(): HasMany
    {
        return $this->hasMany(EventGuest::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(EventMedia::class);
    }

    public function scans(): HasMany
    {
        return $this->hasMany(EventScan::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    // ------------------------------------------------------------------
    // Scopes
    // ------------------------------------------------------------------

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // ------------------------------------------------------------------
    // URLs
    // ------------------------------------------------------------------

    public function portalUrl(): string
    {
        return route('portal.show', $this->slug);
    }

    /** Alamat yang ditanam di QR: begitu discan langsung mode kamera. */
    public function cameraUrl(): string
    {
        return route('portal.camera', ['event' => $this->slug, 'k' => $this->qr_token]);
    }

    public function galleryUrl(): string
    {
        return route('portal.gallery', $this->slug);
    }

    public function coverUrl(): ?string
    {
        return $this->cover_path ? Storage::disk('public')->url($this->cover_path) : null;
    }

    // ------------------------------------------------------------------
    // Aturan buka/tutup
    // ------------------------------------------------------------------

    /** Kamera hanya menerima tangkapan saat event aktif dan dalam jendela waktunya. */
    public function isCaptureOpen(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $now = now();

        if ($this->capture_opens_at && $now->lt($this->capture_opens_at)) {
            return false;
        }

        if ($this->capture_closes_at && $now->gt($this->capture_closes_at)) {
            return false;
        }

        return true;
    }

    public function captureClosedReason(): string
    {
        return match (true) {
            $this->status === 'draft' => 'Acara ini belum dibuka.',
            $this->status === 'paused' => 'Kamera sedang dijeda oleh tuan rumah.',
            in_array($this->status, ['completed', 'archived'], true) => 'Acara sudah selesai. Terima kasih sudah ikut memotret!',
            $this->capture_opens_at && now()->lt($this->capture_opens_at) => 'Kamera terbuka pada ' . $this->capture_opens_at->translatedFormat('d F Y, H:i') . '.',
            default => 'Sesi memotret sudah ditutup.',
        };
    }

    /** Apakah galeri bersama sudah boleh dilihat tamu. */
    public function isGalleryVisible(): bool
    {
        if (! $this->gallery_public) {
            return false;
        }

        return match ($this->gallery_reveal) {
            'instant' => true,
            'manual' => $this->gallery_unlocked,
            default => $this->gallery_unlocked
                || in_array($this->status, ['completed', 'archived'], true)
                || ($this->capture_closes_at && now()->gt($this->capture_closes_at))
                || ($this->event_date && now()->gt($this->event_date->copy()->endOfDay())),
        };
    }

    public function galleryHiddenReason(): string
    {
        if (! $this->gallery_public) {
            return 'Galeri acara ini disembunyikan oleh tuan rumah.';
        }

        return 'Semua hasil jepretan dibuka bersamaan setelah acara selesai. Sabar sedikit ya.';
    }

    // ------------------------------------------------------------------
    // Tampilan
    // ------------------------------------------------------------------

    /**
     * Nama besar yang dipajang di portal: pasangan untuk pernikahan,
     * nama tuan rumah (kampus, sekolah, kantor) untuk acara lain.
     */
    public function getCoupleAttribute(): string
    {
        if ($this->bride_name && $this->groom_name) {
            return $this->bride_name . ' & ' . $this->groom_name;
        }

        return $this->host_name
            ?: ($this->bride_name ?: ($this->groom_name ?: $this->title));
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->event_type] ?? 'Acara';
    }

    public function isWedding(): bool
    {
        return $this->event_type === 'wedding';
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'active' => 'success',
            'draft' => 'secondary',
            'paused' => 'warning',
            'completed' => 'info',
            default => 'dark',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active' => 'Aktif',
            'draft' => 'Draft',
            'paused' => 'Dijeda',
            'completed' => 'Selesai',
            'archived' => 'Diarsipkan',
            default => ucfirst($this->status),
        };
    }

    /** Total tangkapan yang boleh diambil satu tamu. */
    public function getCapturesPerGuestAttribute(): int
    {
        return $this->photo_quota + $this->video_quota + $this->boomerang_quota;
    }
}
