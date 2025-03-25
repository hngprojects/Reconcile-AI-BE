<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Plan;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentPlan>
 */
class PaymentPlanFactory extends Factory
{

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = Carbon::now();
        $expireDate = $startDate->copy()->addDays(30);
        $plan = Plan::factory()->create();

        return [
            'user_id' => User::factory(),
            'plan_id' => $plan->id,
            'stripe_reference' => $this->faker->uuid(),
            'start_date' => $startDate,
            'expire_date' => $expireDate,
            'is_active' => true,
            'price' => $this->faker->randomFloat(2, 10, 100),
            'reconciliations_used' => 0,
            'plan' => $plan->plan,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}