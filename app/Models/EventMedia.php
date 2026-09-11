<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * Satu jepretan tamu: foto, video pendek, atau boomerang.
 */
class EventMedia extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'event_media';

    public const TYPES = ['photo', 'video', 'boomerang'];

    protected $fillable = [
        'event_id',
        'event_guest_id',
        'type',
        'disk',
        'path',
        'thumb_path',
        'poster_path',
        'mime',
        'size',
        'width',
        'height',
        'duration_ms',
        'film_preset',
        'caption',
        'status',
        'is_featured',
        'likes_count',
        'downloads_count',
        'captured_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'captured_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(EventGuest::class, 'event_guest_id');
    }

    public function scopeVisible($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // ------------------------------------------------------------------
    // URLs
    // ------------------------------------------------------------------

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    /** Gambar untuk grid: thumbnail foto, atau poster untuk video. */
    public function previewUrl(): string
    {
        $preview = $this->thumb_path ?: $this->poster_path;

        return $preview
            ? Storage::disk($this->disk)->url($preview)
            : $this->url();
    }

    /**
     * Srcset untuk grid galeri, atau null kalau varian kecilnya tidak ada
     * (media lama yang diunggah sebelum varian ini dibuat).
     *
     * Ponsel menampilkan ubin selebar ±180px, jadi mengirimi mereka
     * thumbnail 720px membuang kuota tamu tanpa terlihat lebih tajam.
     */
    public function previewSrcset(): ?string
    {
        $small = $this->meta['thumb_sm'] ?? null;

        if (! $small || ! $this->thumb_path) {
            return null;
        }

        $disk = Storage::disk($this->disk);

        return $disk->url($small) . ' 360w, ' . $disk->url($this->thumb_path) . ' 720w';
    }

    public function downloadUrl(): string
    {
        return route('portal.media.download', [
            'event' => $this->event->slug,
            'media' => $this->id,
        ]);
    }

    public function isVideoLike(): bool
    {
        return in_array($this->type, ['video', 'boomerang'], true);
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'photo' => 'Foto',
            'video' => 'Video',
            'boomerang' => 'Boomerang',
            default => ucfirst($this->type),
        };
    }

    public function getSizeLabelAttribute(): string
    {
        $size = (int) $this->size;

        if ($size >= 1048576) {
            return round($size / 1048576, 1) . ' MB';
        }

        return max(1, (int) round($size / 1024)) . ' KB';
    }

    /** Hapus berkas fisiknya (dipanggil saat admin menghapus permanen). */
    public function deleteFiles(): void
    {
        $disk = Storage::disk($this->disk);

        foreach ([$this->path, $this->thumb_path, $this->meta['thumb_sm'] ?? null, $this->poster_path] as $file) {
            if ($file && $disk->exists($file)) {
                $disk->delete($file);
            }
        }
    }
}
