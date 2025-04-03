<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\BankAccount;
use App\Models\BusinessInfo;
use Illuminate\Support\Str;

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
            'id' => Str::uuid(),
            'business_infos_id' => BusinessInfo::factory(),
            'bank_name' => $this->faker->company . ' Bank',
            'account_name' => $this->faker->name . ' Business Account',
            'account_number' => $this->faker->numerify('##########'),
            'opening_balance' => $this->faker->randomFloat(2, 100, 10000),
            'is_primary' => $this->faker->boolean,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
