<?php

namespace Database\Factories;

use App\Models\LedgerEntry;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class LedgerEntryFactory extends Factory
{
    protected $model = LedgerEntry::class;

    public function definition()
    {
        return [
            'id' => (string) Str::uuid(), // Add UUID for id field
            'ledger_category' => $this->faker->word,
            'transaction_type' => $this->faker->randomElement(['income', 'expense']),
            'transaction_date' => $this->faker->dateTimeThisYear(),
            'description' => $this->faker->sentence,
            'amount' => $this->faker->randomFloat(2, 100, 10000),
            'paid_status' => $this->faker->randomElement(['paid', 'unpaid', 'partial']),
            'due_date' => $this->faker->dateTimeThisYear(),
            'amount_paid' => $this->faker->randomFloat(2, 0, 10000),
            'account_category' => $this->faker->word,
            'reference' => $this->faker->unique()->regexify('[A-Z]{3}-[0-9]{4}'),
            'attachment' => null
        ];
    }
}