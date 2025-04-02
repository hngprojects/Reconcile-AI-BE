<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Plan>
 */
class PlanFactory extends Factory
{

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'name' => $this->faker->word() . ' Plan',
            'description' => $this->faker->sentence(),
            'plan_length' => 30,
            'plan' => $this->faker->randomElement(['Basic', 'Starter', 'Business']),
            'reconciliations_per_month' => $this->faker->randomElement([5, 20, -1]),
            'amount' => $this->faker->randomFloat(2, 0, 100),
        ];
    }
}