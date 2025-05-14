<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserFile>
 */
class UserFileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'file_name' => $this->faker->word() . '.pdf',
            'file_path' => 'uploads/user_files/' . $this->faker->uuid() . '.pdf',
            'type' => $this->faker->randomElement(['statement', 'ledger']),
            'user_id' => \App\Models\User::factory(),
        ];
    }
}
