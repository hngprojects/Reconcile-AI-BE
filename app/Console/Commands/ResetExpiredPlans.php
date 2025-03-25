<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PaymentPlan;
use App\Models\Plan;
use Illuminate\Support\Carbon;

class ResetExpiredPlans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plans:reset-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset expired plans and downgrade to Basic if needed.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = now();
        $basicPlan = Plan::where('plan', 'Basic')->first();

        if (!$basicPlan) {
            $this->error('Basic plan not found.');
            return;
        }

        $plans = PaymentPlan::where('expire_date', '<', $now)->get();

        foreach ($plans as $plan) {
            if ($plan->plan_id === $basicPlan->id) {
                // Reset Basic plan start and expire date
                $plan->start_date = $now;
                $plan->expire_date = $now->copy()->addDays($basicPlan->plan_length);
                $plan->reconciliations_used = 0;
            } else {
                // Check if grace period has passed
                if ($plan->expire_date->addDays(3)->lessThan($now)) {
                    $plan->plan_id = $basicPlan->id;
                    $plan->start_date = $now;
                    $plan->expire_date = $now->copy()->addDays($basicPlan->plan_length);
                    $plan->reconciliations_used = 0;
                    $plan->price = $basicPlan->amount;
                    $plan->plan = $basicPlan->plan;
                }
            }
            $plan->is_active = false;
            $plan->save();
        }

        $this->info('Expired plans processed successfully.');
    }
}
