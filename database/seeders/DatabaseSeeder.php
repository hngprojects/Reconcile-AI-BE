<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\GuestSeeder;
use Database\Seeders\LedgerSeeder;
use Database\Seeders\StatementSeeder;
use Database\Seeders\MatchingTransactionSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call([
            GuestSeeder::class,
            LedgerSeeder::class,
            StatementSeeder::class,
            MatchingTransactionSeeder::class,
        ]);
    }
}
