<?php

namespace Tests\Feature\Auth;

use App\Models\PaymentPlan;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class UserTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    public function test_authenticated_user_can_fetch_profile()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/v1/user');

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'status',
            'status_code',
            'message',
            'data' => [
                'user' => ['id', 'name', 'email']
            ]
        ]);

        $response->assertJson([
            'status' => 'success',
            'status_code' => 200,
            'message' => 'User successfully fetched',
        ]);

        $response->assertJsonFragment([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }

    /**
     * A basic feature test example.
     */
    /* public function test_authenticated_user_can_fetch_plan_and_profile()
    {
        $user = User::factory()->create();
        $user->paymentPlan()->plan()->create([
            'name' => 'Basic Plan',
            'description' => 'Free trial for 7 days with 5 reconciliations.',
            'plan_length' => 30,
            'plan' => 'Basic',
            'reconciliations_per_month' => 5,
            'amount' => 0.00, 
        ]);
        $user->paymentPlan()->create([
            'user_id' => $user->id,
            'price' => 0,
            'plan' => 'Basic',
        ]);

        $response = $this->actingAs($user)->get('/api/v1/user');
        dd($response->getContent());

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'status',
            'status_code',
            'message',
            'data' => [
                'user' => ['id', 'name', 'email']
            ]
        ]);

        $response->assertJson([
            'status' => 'success',
            'status_code' => 200,
            'message' => 'User successfully fetched',
        ]);

        $response->assertJsonFragment([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);
    } */

    public function test_authenticated_user_can_fetch_plan_and_profile()
    {
        // Create a plan first
        $plan = Plan::create([
            'name' => 'Basic Plan-2',
            'description' => 'Free trial for 7 days with 5 reconciliations.',
            'plan_length' => 30,
            'plan' => 'Basic-2',
            'reconciliations_per_month' => 5,
            'amount' => 0.00,
        ]);

        $user = User::factory()->create();
        
        // Create payment plan for the user
        $paymentPlan = PaymentPlan::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'start_date' => now(),
            'expire_date' => now()->addDays(30),
            'is_active' => true
        ]);

        $response = $this->actingAs($user)->get('/api/v1/user');

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'status',
            'status_code',
            'message',
            'data' => [
                'user' => ['id', 'name', 'email'],
                'plan' => [
                    'id',
                    'user_id',
                    'plan_id',
                    'plan' => [
                        'id',
                        'name',
                        'plan',
                        'description',
                        'plan_length',
                        'reconciliations_per_month',
                        'amount'
                    ],
                    'start_date',
                    'expire_date',
                    'is_active'
                ]
            ]
        ]);

        $response->assertJson([
            'status' => 'success',
            'status_code' => 200,
            'message' => 'User successfully fetched',
        ]);

        // Check specific values using paths
        $response->assertJsonPath('data.plan.plan.plan', 'Basic-2');

        $response->assertJsonFragment([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }
}
