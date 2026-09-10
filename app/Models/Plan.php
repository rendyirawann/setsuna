<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Paket langganan yang dibeli sekali untuk satu acara pernikahan.
 */
class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'tagline',
        'description',
        'price',
        'compare_at_price',
        'currency',
        'max_guests',
        'photo_quota',
        'video_quota',
        'video_duration',
        'boomerang_quota',
        'storage_days',
        'features',
        'is_featured',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'features' => 'array',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** "Rp 4.500.000" */
    public function getPriceLabelAttribute(): string
    {
        return 'Rp ' . number_format((float) $this->price, 0, ',', '.');
    }

    public function getCompareAtPriceLabelAttribute(): ?string
    {
        return $this->compare_at_price
            ? 'Rp ' . number_format((float) $this->compare_at_price, 0, ',', '.')
            : null;
    }

    /** Total tangkapan yang didapat satu tamu dari paket ini. */
    public function getCapturesPerGuestAttribute(): int
    {
        return $this->photo_quota + $this->video_quota + $this->boomerang_quota;
    }
}
