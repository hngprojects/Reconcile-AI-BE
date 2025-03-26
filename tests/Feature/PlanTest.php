<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Plan;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;

class PlanTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_a_plan()
    {
        $payload = [
            'name' => 'Starter Plan',
            'description' => 'This is a starter plan',
            'plan_length' => 30,
            'plan' => 'Starter',
            'amount' => 10.00,
            'reconciliations_per_month' => 10,
        ];

        $response = $this->postJson('/api/v1/plans', $payload);

        $response->assertStatus(201)
                 ->assertJson(['message' => 'Plan created successfully']);

        $this->assertDatabaseHas('plans', ['name' => 'Starter Plan']);
    }

    #[Test]
    public function it_can_fetch_a_plan_by_uuid()
    {
        $plan = Plan::create([
            'id' => Str::uuid(),
            'name' => 'Business Plan-1',
            'description' => 'Business plan with unlimited reconciliations',
            'plan_length' => 365,
            'plan' => 'Business-1',
            'amount' => 25.00,
            'reconciliations_per_month' => -1,
        ]);

        $response = $this->getJson("/api/v1/plans/{$plan->id}");

        $response->assertStatus(200)
                 ->assertJson(['id' => $plan->id, 'name' => 'Business Plan-1']);
    }

    #[Test]
    public function it_can_update_a_plan()
    {
        $plan = Plan::create([
            'id' => Str::uuid(),
            'name' => 'Basic Plan',
            'description' => 'Limited free reconciliations for 7 days',
            'plan_length' => 7,
            'plan' => 'Basic',
            'amount' => 0.00,
            'reconciliations_per_month' => 5,
        ]);

        // Send only a partial update (name & amount)
        $updateData = ['name' => 'Updated Basic Plan', 'amount' => 5.00];

        $response = $this->patchJson("/api/v1/plans/{$plan->id}", $updateData);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Plan updated successfully',
                     'data' => ['name' => 'Updated Basic Plan', 'amount' => 5.00]
                 ]);

        $this->assertDatabaseHas('plans', ['id' => $plan->id, 'name' => 'Updated Basic Plan']);
    }

    #[Test]
    public function it_can_delete_a_plan()
    {
        $plan = Plan::create([
            'id' => Str::uuid(),
            'name' => 'Starter Plan',
            'plan_length' => 30,
            'plan' => 'Starter',
            'amount' => 10.00,
            'reconciliations_per_month' => 10,
        ]);

        $response = $this->deleteJson("/api/v1/plans/{$plan->id}");

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Plan deleted successfully']);

        $this->assertDatabaseMissing('plans', ['id' => $plan->id]);
    }
}