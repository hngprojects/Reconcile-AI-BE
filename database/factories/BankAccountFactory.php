<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BankAccount>
 */
class BankAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->create(),
            'bank_name' => fake()->word(),
            'account_number' => fake()->randomNumber(),
            'account_name' => fake()->word(),
            'is_default' => false,
            'opening_balance' => fake()->randomNumber(),
            'currency' => fake()->currencyCode()
        ];
    }
}
