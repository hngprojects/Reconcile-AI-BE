<?php

namespace Database\Factories;

use App\Models\ReconciledRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReconciledRecordFactory extends Factory
{
    protected $model = ReconciledRecord::class;

    public function definition()
    {
        return [
            'user_id' => \App\Models\User::factory(), // Creates a related User if not provided
            'data' => [
                'matches' => [
                    [
                        'file1_transaction' => ['name' => $this->faker->name, 'amount' => 100],
                        'file2_transaction' => ['name' => $this->faker->name, 'amount' => 100],
                        'match_score' => 95,
                    ],
                ],
                'only_in_file1' => [['name' => $this->faker->name, 'amount' => 300]],
                'only_in_file2' => [['name' => $this->faker->name, 'amount' => 400]],
                'unmatched' => [
                    'unmatched_file1' => [['name' => $this->faker->name, 'amount' => 300]],
                    'unmatched_file2' => [['name' => $this->faker->name, 'amount' => 400]],
                ],
                'matchSummary' => [
                    'totalMatched' => 1,
                    'totalUnmatchedFile1' => 1,
                    'totalUnmatchedFile2' => 1,
                    'totalUnmatched' => 2,
                ],
            ],
        ];
    }
}