<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Plan;
use App\Models\Reconciliation;
use Illuminate\Support\Facades\Log;

class FixUserPlans extends Command
{
    protected $signature = 'plans:fix-user-plans';
    protected $description = 'Check user plans, fix missing start/expire dates, and update reconciliations.';

    public function handle()
    {
        $this->info("Checking user plans...");
        Log::info("Starting FixUserPlans command...");

        $basicPlan = Plan::where('plan', 'Basic')->first();
        if (!$basicPlan) {
            $this->error("Basic plan not found. Please make sure it exists.");
            Log::error("Basic plan not found. FixUserPlans command stopped.");
            return;
        }

        // Loop through all users
        User::with('paymentPlan')->chunk(100, function ($users) use ($basicPlan) {
            foreach ($users as $user) {
                $paymentPlan = $user->paymentPlan;

                if (!$paymentPlan) {
                    Log::info("Skipping user {$user->id} (No payment plan found).");
                    continue;
                }

                if ($paymentPlan->start_date && $paymentPlan->expire_date) {
                    Log::info("Skipping user {$user->id} (Plan already has start & expire dates).");
                    continue;
                }

                if ($paymentPlan->plan_id === $basicPlan->id) {
                    // Basic Plan: Set start_date as registration date
                    $paymentPlan->start_date = $user->created_at;
                    $paymentPlan->expire_date = $user->created_at->copy()->addDays($basicPlan->plan_length);
                    Log::info("Updated Basic Plan for user {$user->id}: start_date={$paymentPlan->start_date}, expire_date={$paymentPlan->expire_date}");
                } else {
                    // Other plans: Set start_date as current date
                    $paymentPlan->start_date = now();
                    $paymentPlan->expire_date = now()->addDays($paymentPlan->plan->plan_length);
                    Log::info("Updated Non-Basic Plan for user {$user->id}: start_date={$paymentPlan->start_date}, expire_date={$paymentPlan->expire_date}");
                }

                $paymentPlan->save();
            }
        });

        $this->info("User plans checked and updated!");
        Log::info("User plans checked and updated!");

        // Update reconciliation counts
        $this->updateReconciliationCounts();
    }

    private function updateReconciliationCounts()
    {
        $this->info("Updating reconciliation counts...");
        Log::info("Starting reconciliation update...");

        User::chunk(100, function ($users) {
            foreach ($users as $user) {
                $paymentPlan = $user->paymentPlan;

                if (!$paymentPlan) {
                    Log::info("Skipping reconciliation update for user {$user->id} (No payment plan).");
                    continue;
                }

                // Count reconciliations
                $reconciliationCount = Reconciliation::where('user_id', $user->id)->count();

                // Update the reconciliation count in the payment plan
                $paymentPlan->reconciliations_used = $reconciliationCount;
                $paymentPlan->save();

                Log::info("Updated reconciliation count for user {$user->id}: reconciliations_used={$reconciliationCount}");
            }
        });

        $this->info("Reconciliation counts updated!");
        Log::info("Reconciliation counts updated!");
    }
}
