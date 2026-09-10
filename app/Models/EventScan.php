<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Satu baris per QR yang discan. */
class EventScan extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'event_id',
        'event_guest_id',
        'ip',
        'device',
        'platform',
        'browser',
        'user_agent',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(EventGuest::class, 'event_guest_id');
    }
}
