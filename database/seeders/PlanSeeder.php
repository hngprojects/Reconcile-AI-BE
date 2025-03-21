<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use App\Models\User;

// First user: reconxi02@gmail.com
$user = User::where('email', 'reconxi02@gmail.com')->first();

if (!$user) {
    $user = User::firstOrCreate(
        ['email' => 'reconxi02@gmail.com'],
        ['name' => 'reconxi02', 'password' => bcrypt(uniqid()), 'avatar' => null]
    );
}

// 🔹 Check if the user already has a plan
$existingPlan = DB::table('payment_plans')->where('user_id', $user->id)->first();

if ($existingPlan) {
    // 🔹 Update the existing plan
    DB::table('payment_plans')
        ->where('user_id', $user->id)
        ->update([
            'plan' => 'Starter Plan', // Change to the new plan name
            'price' => 10, // Update the price if needed
            'updated_at' => now(),
        ]);
} else {
    // 🔹 Insert a new plan if the user doesn't have one
    DB::table('payment_plans')->insert([
        'user_id' => $user->id,
        'plan' => 'Starter Plan',
        'price' => 10,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

// Second user: mark@hotels.ng
$markUser = User::where('email', 'mark@hotels.ng')->first();

if (!$markUser) {
    $markUser = User::firstOrCreate(
        ['email' => 'mark@hotels.ng'],
        ['name' => 'Mark', 'password' => bcrypt(uniqid()), 'avatar' => null]
    );
}

// 🔹 Check if the user already has a plan
$existingMarkPlan = DB::table('payment_plans')->where('user_id', $markUser->id)->first();

if ($existingMarkPlan) {
    // 🔹 Update the existing plan
    DB::table('payment_plans')
        ->where('user_id', $markUser->id)
        ->update([
            'plan' => 'Starter Plan', // Change to the new plan name
            'price' => 10, // Update the price if needed
            'updated_at' => now(),
        ]);
} else {
    // 🔹 Insert a new plan if the user doesn't have one
    DB::table('payment_plans')->insert([
        'user_id' => $markUser->id,
        'plan' => 'Starter Plan',
        'price' => 10,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}
