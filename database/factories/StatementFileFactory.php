<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\UserFile;
use App\Models\BankAccount;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StatementFile>
 */
class StatementFileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bank_account_id' => BankAccount::factory(),
            'start_date' => $this->faker->date(),
            'end_date' => $this->faker->date(),
            'user_file_id' => UserFile::factory(),
        ];
    }
}
