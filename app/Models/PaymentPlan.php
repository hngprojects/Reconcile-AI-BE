<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentPlan extends Model
{
    //

    protected $guarded = [];

    protected $casts = [
        'start_date'    => 'datetime',
        'expire_date'   => 'datetime',
        'is_active'     => 'boolean'
    ];

    // Automatically deactivate expired plans
    protected static function booted()
    {
        static::saving(function ($plan) {
            // Auto-deactivate plans when they expire
            if ($plan->expire_date < now()) {
                $plan->is_active = false;
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function isActive(): bool
    {
        return now()->between($this->start_date, $this->expire_date);
    }

    public function daysRemaining(): int
    {
        return now()->diffInDays($this->expire_date, false);
    }

    /* public function isExpired(): bool
    {
        return $this->expire_date < now();
    } */

    public function isExpired(): bool {
        return now()->greaterThan($this->expire_date);
    }
}
