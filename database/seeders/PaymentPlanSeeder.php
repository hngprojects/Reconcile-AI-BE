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

        $user1 = User::firstOrCreate(
            ['email' => 'goke.vincent@gmail.com'],
            ['name' => 'Goke vincent', 'password' => bcrypt(uniqid()), 'avatar' => null]
        );


        // 🔹 Check if the user already has a plan
        $existingPlan = DB::table('payment_plans')->where('user_id', $user->id)->first();
        $existingPlan1 = DB::table('payment_plans')->where('user_id', $user1->id)->first();
        $planstarter = DB::table('plans')->where('plan', 'Starter')->first();
        $planbusiness = DB::table('plans')->where('plan', 'Business')->first();

        if ($existingPlan) {
            // 🔹 Update the existing plan
            DB::table('payment_plans')
                ->where('user_id', $user->id)
                ->update([
                    'plan' => 'Starter',
                    'price' => 10.00, // Updated to decimal format
                    'updated_at' => now(),
                    'plan_id' => $planstarter->id,
                ]);
        } else {
            // 🔹 Insert a new plan if the user doesn't have one
            DB::table('payment_plans')->insert([
                'user_id' => $user->id,
                'plan' => 'Starter',
                'price' => 10.00, // Updated to decimal format
                'created_at' => now(),
                'updated_at' => now(),
                'plan_id' => $planstarter->id,
            ]);
        }
        if ($existingPlan1) {
            // 🔹 Update the existing plan
            DB::table('payment_plans')
                ->where('user_id', $user1->id)
                ->update([
                    'plan' => 'Business',
                    'price' => 25.00, // Updated to decimal format
                    'updated_at' => now(),
                    'plan_id' => $planbusiness->id,
                ]);
        } else {
            // 🔹 Insert a new plan if the user doesn't have one
            DB::table('payment_plans')->insert([
                'user_id' => $user1->id,
                'plan' => 'Business',
                'price' => 25.00, // Updated to decimal format
                'created_at' => now(),
                'updated_at' => now(),
                'plan_id' => $planbusiness->id,
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
                    'plan' => 'Starter',
                    'price' => 10.00, // Updated to decimal format
                    'updated_at' => now(),
                    'plan_id' => $planstarter->id,
                ]);
        } else {
            // 🔹 Insert a new plan if the user doesn't have one
            DB::table('payment_plans')->insert([
                'user_id' => $markUser->id,
                'plan' => 'Starter',
                'price' => 10.00, // Updated to decimal format
                'created_at' => now(),
                'updated_at' => now(),
                'plan_id' => $planstarter->id,
            ]);
        }
    }
}