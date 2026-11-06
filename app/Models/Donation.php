<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Donation extends Model
{
    public const STATUS_TERCATAT = 'tercatat';
    public const STATUS_DIBATALKAN = 'dibatalkan';

    protected $fillable = [
        'user_id', 'recorded_by', 'blood_bag_code', 'blood_type', 'donation_date',
        'volume_ml', 'notes', 'status', 'cancel_reason', 'cancelled_at',
    ];

    protected $casts = [
        'donation_date' => 'date',
        'cancelled_at'  => 'datetime',
    ];

    public function donor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function scopeTercatat($query)
    {
        return $query->where('status', self::STATUS_TERCATAT);
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_DIBATALKAN;
    }
}