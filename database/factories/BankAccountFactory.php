<?php

namespace Database\Factories;

use App\Models\BankAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BankAccountFactory extends Factory
{
    protected $model = BankAccount::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'account_name' => $this->faker->words(2, true),
            'account_number' => $this->faker->numerify('##########'),
            'bank_name' => $this->faker->company,
            'opening_balance' => $this->faker->randomFloat(2, 0, 10000),
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'GBP']),
            'is_default' => false,
        ];
    }
} 