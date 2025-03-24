<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class PaymentPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // First user: reconxi02@gmail.com
        $user = User::firstOrCreate(
            ['email' => 'reconxi02@gmail.com'],
            ['name' => 'reconxi02', 'password' => bcrypt(uniqid()), 'avatar' => null]
        );

        // 🔹 Check if the user already has a plan
        $existingPlan = DB::table('payment_plans')->where('user_id', $user->id)->first();

        if ($existingPlan) {
            // 🔹 Update the existing plan
            DB::table('payment_plans')
                ->where('user_id', $user->id)
                ->update([
                    'plan' => 'Starter Plan',
                    'price' => 10.00, // Updated to decimal format
                    'updated_at' => now(),
                ]);
        } else {
            // 🔹 Insert a new plan if the user doesn't have one
            DB::table('payment_plans')->insert([
                'user_id' => $user->id,
                'plan' => 'Starter Plan',
                'price' => 10.00, // Updated to decimal format
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Second user: mark@hotels.ng
        $markUser = User::firstOrCreate(
            ['email' => 'mark@hotels.ng'],
            ['name' => 'Mark', 'password' => bcrypt(uniqid()), 'avatar' => null]
        );

        // 🔹 Check if the user already has a plan
        $existingMarkPlan = DB::table('payment_plans')->where('user_id', $markUser->id)->first();

        if ($existingMarkPlan) {
            // 🔹 Update the existing plan
            DB::table('payment_plans')
                ->where('user_id', $markUser->id)
                ->update([
                    'plan' => 'Starter Plan',
                    'price' => 10.00, // Updated to decimal format
                    'updated_at' => now(),
                ]);
        } else {
            // 🔹 Insert a new plan if the user doesn't have one
            DB::table('payment_plans')->insert([
                'user_id' => $markUser->id,
                'plan' => 'Starter Plan',
                'price' => 10.00, // Updated to decimal format
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}