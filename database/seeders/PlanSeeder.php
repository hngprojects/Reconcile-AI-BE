<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;
use Illuminate\Support\Str;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'id' => Str::uuid(),
                'name' => 'Basic Plan',
                'description' => 'Free trial for 7 days with 5 reconciliations.',
                'plan_length' => 30,
                'plan' => 'Basic',
                'reconciliations_per_month' => 5,
                'amount' => 0.00, // Free plan
                // 'expiration_days' => 7,
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Starter Plan',
                'description' => 'Starter plan with 10 reconciliations per month.',
                'plan_length' => 30,
                'plan' => 'Starter',
                'reconciliations_per_month' => 20,
                'amount' => 10.00, // $10.00 per month
                // 'expiration_days' => null,
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Business Plan',
                'description' => 'Business plan with unlimited reconciliations.',
                'plan_length' => 30,
                'plan' => 'Business',
                'reconciliations_per_month' => -1,
                'amount' => 25.00, // $25.00 per month
                // 'expiration_days' => null,
            ],
        ];        

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['plan' => $plan['plan']], $plan);
        }
    }
}
