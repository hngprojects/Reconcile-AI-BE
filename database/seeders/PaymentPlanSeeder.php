<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Plan;
use Illuminate\Support\Facades\DB;

class PaymentPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define users and their plans
        $usersData = [
            ['email' => 'reconxi02@gmail.com', 'name' => 'Reconxi02', 'plan' => 'Starter', 'price' => 10.00],
            ['email' => 'goke.vincent@gmail.com', 'name' => 'Goke Vincent', 'plan' => 'Business', 'price' => 25.00],
            ['email' => 'mark@hotels.ng', 'name' => 'Mark', 'plan' => 'Starter', 'price' => 10.00],
        ];

        // Fetch all plans at once
        $plans = Plan::whereIn('plan', ['Starter', 'Business'])->pluck('id', 'plan');

        foreach ($usersData as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                ['name' => $userData['name'], 'password' => bcrypt(uniqid()), 'avatar' => null]
            );

            // Ensure the plan exists before proceeding
            if (!isset($plans[$userData['plan']])) {
                continue;
            }

            // Upsert (Insert or Update) payment plan
            DB::table('payment_plans')->updateOrInsert(
                ['user_id' => $user->id],
                [
                    'plan_id'     => $plans[$userData['plan']],
                    'updated_at'  => now(),
                    'created_at'  => now(),
                ]
            );
        }
    }
}