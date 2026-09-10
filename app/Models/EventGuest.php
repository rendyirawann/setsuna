<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Satu tamu = satu perangkat yang scan QR. Token disimpan di cookie,
 * jadi tamu tidak perlu login untuk melanjutkan rollnya.
 */
class EventGuest extends Model
{
    use HasFactory, HasUuids;

    /** Warna label nama di galeri. */
    public const COLORS = ['#e8c8a0', '#c9a7eb', '#9ec5f5', '#f2a2b7', '#a8d8c0', '#f0c987'];

    protected $fillable = [
        'event_id',
        'name',
        'token',
        'color',
        'photo_quota',
        'video_quota',
        'boomerang_quota',
        'photos_used',
        'videos_used',
        'boomerangs_used',
        'is_blocked',
        'ip',
        'device',
        'user_agent',
        'last_active_at',
    ];

    protected function casts(): array
    {
        return [
            'is_blocked' => 'boolean',
            'last_active_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(EventMedia::class);
    }

    // ------------------------------------------------------------------
    // Kuota
    // ------------------------------------------------------------------

    /** Sisa jatah untuk satu jenis tangkapan. */
    public function remaining(string $type): int
    {
        return match ($type) {
            'photo' => max(0, $this->photo_quota - $this->photos_used),
            'video' => max(0, $this->video_quota - $this->videos_used),
            'boomerang' => max(0, $this->boomerang_quota - $this->boomerangs_used),
            default => 0,
        };
    }

    public function canCapture(string $type): bool
    {
        return ! $this->is_blocked && $this->remaining($type) > 0;
    }

    /** Kolom penghitung pemakaian untuk satu jenis tangkapan. */
    public static function usageColumn(string $type): string
    {
        return match ($type) {
            'photo' => 'photos_used',
            'video' => 'videos_used',
            'boomerang' => 'boomerangs_used',
            default => throw new \InvalidArgumentException("Jenis tangkapan tidak dikenal: {$type}"),
        };
    }

    /** @return array<string,int> Ringkasan kuota untuk dikirim ke kamera. */
    public function quotaSummary(): array
    {
        return [
            'photo' => $this->remaining('photo'),
            'video' => $this->remaining('video'),
            'boomerang' => $this->remaining('boomerang'),
            'photo_total' => $this->photo_quota,
            'video_total' => $this->video_quota,
            'boomerang_total' => $this->boomerang_quota,
        ];
    }

    public function getInitialsAttribute(): string
    {
        return mb_strtoupper(mb_substr($this->name, 0, 1));
    }

    public function getTotalCapturesAttribute(): int
    {
        return $this->photos_used + $this->videos_used + $this->boomerangs_used;
    }
}
