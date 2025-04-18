<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ledger>
 */
class LedgerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => fake()->date(),
            'person' => fake()->name(),
            'other_information' => fake()->sentence(),
            'amount' => fake()->randomNumber(),
            'transaction_type' => fake()->word(),
            'bookkeeping_ledger_id' => \App\Models\BookkeepingLedger::factory()
        ];
    }
}
