<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pesanan paket untuk satu acara. Event dibuka setelah statusnya "paid".
 */
class Subscription extends Model
{
    use HasFactory;

    public const STATUSES = ['pending', 'paid', 'expired', 'cancelled', 'refunded'];

    protected $fillable = [
        'invoice_number',
        'client_id',
        'plan_id',
        'event_id',
        'amount',
        'discount',
        'total',
        'currency',
        'status',
        'payment_method',
        'payment_reference',
        'snap_token',
        'payment_payload',
        'paid_at',
        'due_at',
        'starts_at',
        'ends_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'payment_payload' => 'array',
            'paid_at' => 'datetime',
            'due_at' => 'datetime',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /** INV/2026/09/0007 */
    public static function nextInvoiceNumber(): string
    {
        $prefix = 'INV/' . now()->format('Y/m') . '/';
        $count = static::where('invoice_number', 'like', $prefix . '%')->count() + 1;

        return $prefix . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }

    public function getTotalLabelAttribute(): string
    {
        return 'Rp ' . number_format((float) $this->total, 0, ',', '.');
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'paid' => 'success',
            'pending' => 'warning',
            'expired' => 'secondary',
            'cancelled' => 'danger',
            'refunded' => 'info',
            default => 'dark',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'paid' => 'Lunas',
            'pending' => 'Menunggu Pembayaran',
            'expired' => 'Kedaluwarsa',
            'cancelled' => 'Dibatalkan',
            'refunded' => 'Dikembalikan',
            default => ucfirst($this->status),
        };
    }
}
