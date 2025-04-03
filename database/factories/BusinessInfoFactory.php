<?php

namespace Database\Factories;

use App\Models\BusinessInfo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BusinessInfo>
 */
class BusinessInfoFactory extends Factory
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
            'name' => $this->faker->company,
            'type' => $this->faker->randomElement(['sole_proprietorship', 'partnership', 'llc', 'corporation']),
            'reporting_year' => 'January-December',
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'GBP', 'NGN']),
            'user_id' => User::factory(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
