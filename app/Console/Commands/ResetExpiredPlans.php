<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PaymentPlan;
use App\Models\Plan;
use Illuminate\Support\Facades\Log;

class ResetExpiredPlans extends Command
{
    protected $signature = 'plans:reset-expired';
    protected $description = 'Reset expired plans and downgrade to Basic if needed.';

    public function handle()
    {
        $now = now();
        Log::info('Starting ResetExpiredPlans command at ' . $now);

        $basicPlan = Plan::where('plan', 'Basic')->first();

        if (!$basicPlan) {
            $this->error('Basic plan not found.');
            Log::error('Basic plan not found. ResetExpiredPlans command stopped.');
            return;
        }

        $plans = PaymentPlan::where('expire_date', '<', $now)->get();
        Log::info('Found ' . $plans->count() . ' expired plans.');

        foreach ($plans as $plan) {
            Log::info("Processing user {$plan->user_id} with plan {$plan->plan_id}");

            if ($plan->plan_id === $basicPlan->id) {
                // Reset Basic plan start and expire date
                $plan->start_date = $now;
                $plan->expire_date = $now->copy()->addDays($basicPlan->plan_length);
                $plan->reconciliations_used = 0;

                Log::info("User {$plan->user_id} is on Basic plan. Reset dates: start_date={$plan->start_date}, expire_date={$plan->expire_date}");
            } else {
                // Check if grace period has passed
                if ($plan->expire_date->addDays(3)->lessThan($now)) {
                    $plan->plan_id = $basicPlan->id;
                    $plan->start_date = $now;
                    $plan->expire_date = $now->copy()->addDays($basicPlan->plan_length);
                    $plan->reconciliations_used = 0;
                    $plan->price = $basicPlan->amount;
                    $plan->plan = $basicPlan->plan;

                    Log::info("User {$plan->user_id} downgraded to Basic plan: start_date={$plan->start_date}, expire_date={$plan->expire_date}");
                }
            }

            $plan->is_active = false;
            $plan->save();
            Log::info("User {$plan->user_id} plan updated and deactivated.");
        }

        $this->info('Expired plans processed successfully.');
        Log::info('ResetExpiredPlans command completed.');
    }
}
