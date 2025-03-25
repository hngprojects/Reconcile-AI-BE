<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentPlan extends Model
{
    use HasFactory;

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
            $now = now();
            $basicPlan = Plan::where('name', 'Basic')->first(); // Get the Basic plan

            if (!$basicPlan) {
                return; // Prevent errors if no Basic plan exists
            }

            // If the plan is expired
            if ($plan->expire_date < $now) {
                // If the plan is already Basic, reset start and expire dates
                if ($plan->plan_id === $basicPlan->id) {
                    $plan->start_date = $now;
                    $plan->expire_date = $now->copy()->addDays($basicPlan->plan_length);
                    $plan->reconciliations_used = 0;
                } 
                // If it's not Basic, check for the extra 3-day grace period
                else {
                    if ($plan->expire_date->addDays(3)->lessThan($now)) {
                        // Convert to Basic plan
                        $plan->plan_id = $basicPlan->id;
                        $plan->start_date = $now;
                        $plan->expire_date = $now->copy()->addDays($basicPlan->plan_length);
                        $plan->reconciliations_used = 0;
                        $plan->price = $basicPlan->amounnt;
                        $plan->plan = $basicPlan->plan;
                    }
                }
                // Deactivate plan
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
        // return $this->belongsTo(Plan::class);
        return $this->belongsTo(Plan::class, 'plan_id', 'id');
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
