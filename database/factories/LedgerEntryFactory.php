<?php

namespace Database\Factories;

use App\Models\LedgerEntry;
use App\Models\User;
use App\Models\BookkeepingLedger;
use Illuminate\Database\Eloquent\Factories\Factory;

class LedgerEntryFactory extends Factory
{
    protected $model = LedgerEntry::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'ledger_id' => BookkeepingLedger::factory(),
            'account_category' => $this->faker->randomElement(['Cash', 'Bank', 'Accounts Receivable', 'Accounts Payable', 'Sales Revenue', 'Rent Expense', 'Utilities Expense', 'Salary Expense']),
            'transaction_type' => $this->faker->randomElement(['income', 'expense']),
            'date' => $this->faker->date(),
            'description' => $this->faker->sentence,
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'paid_status' => $this->faker->boolean,
            'bank_account_id' => null,
            'invoice_or_ref_number' => $this->faker->unique()->numerify('INV-####'),
            'attachment' => null,
            'notes' => $this->faker->paragraph,
        ];
    }
} 