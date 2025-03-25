<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use App\Models\PaymentPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
// use Carbon\Carbon;
use Illuminate\Support\Str;

class PaymentPlanControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $plan;
    protected $enterprisePlan;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user
        $this->user = User::factory()->create();

        // Create test plans
        $this->plan = Plan::create([
            'id' => Str::uuid(),
            'name' => 'Premium Plan',
            'plan' => 'Premium',
            'description' => 'Premium subscription plan',
            'plan_length' => 30,
            'reconciliations_per_month' => 100,
            'amount' => 19.99
        ]);

        $this->enterprisePlan = Plan::create([
            'id' => Str::uuid(),
            'name' => 'Enterprise Plan',
            'plan' => 'Enterprise',
            'description' => 'Enterprise subscription plan',
            'plan_length' => 365,
            'reconciliations_per_month' => 1000,
            'amount' => 99.99
        ]);
    }

    /** @test */
    public function user_can_create_first_payment_plan()
    {
        // Authenticate the user
        $this->actingAs($this->user);

        // Prepare request data
        $requestData = [
            'price' => 19.99,
            'plan' => 'Premium'
        ];

        // Send request to create payment plan
        $response = $this->postJson('/api/v1/payment-plan', $requestData);

        // Assert response
        $response->assertStatus(201)
            ->assertJson([
                'status' => true,
                'message' => 'Payment plan created successfully.'
            ]);

        // Check database
        $this->assertDatabaseHas('payment_plans', [
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'price' => 19.99,
            'plan' => 'Premium'
        ]);
    }

    /** @test */
    public function cannot_create_plan_with_incorrect_price()
    {
        // Authenticate the user
        $this->actingAs($this->user);

        // Prepare request data with incorrect price
        $requestData = [
            'price' => 29.99,  // Incorrect price
            'plan' => 'Premium'
        ];

        // Send request to create payment plan
        $response = $this->postJson('/api/v1/payment-plan', $requestData);

        // Assert error response
        $response->assertStatus(400)
            ->assertJson([
                'status' => false,
                'message' => 'The price must match the plan amount.',
                'expected_price' => 19.99
            ]);
    }

    /** @test */
    public function cannot_create_plan_with_invalid_plan_name()
    {
        // Authenticate the user
        $this->actingAs($this->user);

        // Prepare request data with non-existent plan
        $requestData = [
            'price' => 19.99,
            'plan' => 'NonExistentPlan'
        ];

        // Send request to create payment plan
        $response = $this->postJson('/api/v1/payment-plan', $requestData);

        // Assert error response
        $response->assertStatus(400)
            ->assertJson([
                'status' => false,
                'message' => 'Invalid plan name.'
            ]);
    }

    /** @test */
    public function cannot_create_new_plan_before_existing_plan_expires()
    {
        // Create an active plan that's not close to expiration
        $existingPlan = PaymentPlan::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'plan' => 'Premium',
            'price' => 19.99,
            'start_date' => now(),
            'expire_date' => now()->addDays(10),
            'is_active' => true
        ]);

        // Authenticate the user
        $this->actingAs($this->user);

        // Prepare request data
        $requestData = [
            'price' => 19.99,
            'plan' => 'Premium'
        ];

        // Send request to create payment plan
        $response = $this->postJson('/api/v1/payment-plan', $requestData);

        // Assert error response
        $response->assertStatus(400)
            ->assertJson([
                'status' => false,
                'message' => 'You cannot subscribe until your current plan is close to expiration.'
            ]);
    }

    /** @test */
    public function can_create_new_plan_when_existing_plan_is_close_to_expiration()
    {
        // Create an active plan that's close to expiration
        $existingPlan = PaymentPlan::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'plan' => 'Premium',
            'price' => 19.99,
            'start_date' => now()->subDays(25),
            'expire_date' => now()->addDays(5),
            'is_active' => true
        ]);

        // Authenticate the user
        $this->actingAs($this->user);

        // Prepare request data
        $requestData = [
            'price' => 19.99,
            'plan' => 'Premium'
        ];

        // Send request to create payment plan
        $response = $this->postJson('/api/v1/payment-plan', $requestData);

        // Assert successful response
        $response->assertStatus(201)
            ->assertJson([
                'status' => true,
                'message' => 'Payment plan created successfully.'
            ]);

        // Check database for new plan
        $this->assertDatabaseHas('payment_plans', [
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'plan' => 'Premium'
        ]);
    }

    /** @test */
    public function can_update_plan_when_close_to_expiration()
    {
        // Create an active plan that's close to expiration
        $existingPlan = PaymentPlan::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'plan' => 'Premium',
            'price' => 19.99,
            'start_date' => now()->subDays(25),
            'expire_date' => now()->addDays(5),
            'is_active' => true
        ]);

        // Authenticate the user
        $this->actingAs($this->user);

        // Prepare request data for update
        $requestData = [
            'price' => 99.99,
            'plan' => 'Enterprise'
        ];

        // Send request to update payment plan
        $response = $this->putJson('/api/v1/payment-plan', $requestData);

        // Assert successful response
        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => 'Payment plan updated successfully.'
            ]);

        // Refresh the existing plan
        $updatedPlan = PaymentPlan::find($existingPlan->id);

        // Check database for updated plan
        $this->assertEquals($this->enterprisePlan->id, $updatedPlan->plan_id);
        $this->assertEquals('Enterprise', $updatedPlan->plan);
        $this->assertEquals(99.99, $updatedPlan->price);
    }

    /** @test */
    public function cannot_update_plan_before_expiration()
    {
        // Create an active plan that's not close to expiration
        $existingPlan = PaymentPlan::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'plan' => 'Premium',
            'price' => 19.99,
            'start_date' => now(),
            'expire_date' => now()->addDays(10),
            'is_active' => true
        ]);

        // Authenticate the user
        $this->actingAs($this->user);

        // Prepare request data for update
        $requestData = [
            'price' => 99.99,
            'plan' => 'Enterprise'
        ];

        // Send request to update payment plan
        $response = $this->putJson('/api/v1/payment-plan', $requestData);

        // Assert error response
        $response->assertStatus(400)
            ->assertJson([
                'status' => false,
                'message' => 'Cannot update plan until it\'s close to expiration.'
            ]);
    }

    /** @test */
    public function cannot_update_plan_with_incorrect_price()
    {
        // Create an active plan that's close to expiration
        $existingPlan = PaymentPlan::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'plan' => 'Premium',
            'price' => 19.99,
            'start_date' => now()->subDays(25),
            'expire_date' => now()->addDays(5),
            'is_active' => true
        ]);

        // Authenticate the user
        $this->actingAs($this->user);

        // Prepare request data for update with incorrect price
        $requestData = [
            'price' => 59.99,  // Incorrect price
            'plan' => 'Enterprise'
        ];

        // Send request to update payment plan
        $response = $this->putJson('/api/v1/payment-plan', $requestData);

        // Assert error response
        $response->assertStatus(400)
            ->assertJson([
                'status' => false,
                'message' => 'The price must match the plan amount.',
                'expected_price' => 99.99
            ]);
    }

    /** @test */
    public function cannot_update_plan_with_invalid_plan_name()
    {
        // Create an active plan that's close to expiration
        $existingPlan = PaymentPlan::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'plan' => 'Premium',
            'price' => 19.99,
            'start_date' => now()->subDays(25),
            'expire_date' => now()->addDays(5),
            'is_active' => true
        ]);

        // Authenticate the user
        $this->actingAs($this->user);

        // Prepare request data for update with non-existent plan
        $requestData = [
            'price' => 99.99,
            'plan' => 'NonExistentPlan'
        ];

        // Send request to update payment plan
        $response = $this->putJson('/api/v1/payment-plan', $requestData);

        // Assert error response
        $response->assertStatus(400)
            ->assertJson([
                'status' => false,
                'message' => 'Invalid plan name.'
            ]);
    }

    /** @test */
    public function show_returns_active_payment_plan()
    {
        // Create an active plan using the seeded plan
        $existingPlan = PaymentPlan::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,  // Use the seeded plan
            'plan' => 'Premium',           // Match the seeded plan name
            'price' => 19.99,
            'start_date' => now()->subDays(10),
            'expire_date' => now()->addDays(40),
            'is_active' => true
        ]);

        // Authenticate the user
        $this->actingAs($this->user);

        // Send request to show payment plan
        $response = $this->getJson('/api/v1/payment-plan');
        // Assert successful response
        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data' => [
                    'id' => $existingPlan->id,
                    'plan' => 'Premium'  // Expect Premium, not Business
                ]
            ]);

        // Check that days_remaining and is_expired are present
        $responseData = $response->json('data');
        $this->assertArrayHasKey('days_remaining', $responseData);
        $this->assertArrayHasKey('is_expired', $responseData);
    }

    /** @test */
    public function show_returns_404_when_no_active_plan()
    {
        // Authenticate the user
        $this->actingAs($this->user);

        // Send request to show payment plan
        $response = $this->getJson('/api/v1/payment-plan');

        // Assert 404 response
        $response->assertStatus(404)
            ->assertJson([
                'status' => false,
                'message' => 'No active payment plan found'
            ]);
    }
}