<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User; // Import User model

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        // 🔹 Retrieve an existing user by email or ID
        $user = User::where('email', 'reconxi02@gmail.com')->first();

        if (!$user) {
            $user = User::firstOrCreate(
                ['email' => 'reconxi02@gmail.com'],
                ['name' => 'reconxi02', 'password' => bcrypt(uniqid()),  'avatar' => null,]
            );
        }

        // 🔹 Insert a new plan linked to the user
        DB::table('payment_plans')->insert([
            'user_id' => $user->id, // Assign the existing user ID
            'plan' => ' Starter Plan',
            'price' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info('Plan seeded successfully for user: ' . $user->email);
    }
}
