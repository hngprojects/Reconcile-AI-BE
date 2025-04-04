<?php

namespace Database\Factories;

use App\Models\BookkeepingLedger;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookkeepingLedgerFactory extends Factory
{
    protected $model = BookkeepingLedger::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence,
            'categories' => ['Assets', 'Revenue', 'Liabilities', 'Expenses', 'Equity'],
            'is_active' => true,
            'is_default' => false,
        ];
    }
} 