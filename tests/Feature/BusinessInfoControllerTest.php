<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\BusinessInfo;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BusinessInfoControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test successful creation of business information.
     */
    public function testStoreBusinessInfoSuccess()
    {
        // Create a test user
        $user = User::factory()->create();

        // Mock valid request data
        $data = [
            'business_name' => 'Test Business',
            'business_type' => 'Retail',
            'currency' => 'USD',
            'reporting_year' => 'January - December',
        ];

        // Act as the user and make a POST request
        $response = $this->actingAs($user, 'api')
            ->postJson('/api/v1/business-info', $data);

        // Assert the response
        $response->assertStatus(201);
        $response->assertJson([
            'code' => 201,
            'message' => 'Business info created successfully',
            'data' => [
                'name' => 'Test Business',
                'type' => 'Retail',
                'currency' => 'USD',
                'reporting_year' => 'January - December',
            ],
            'error' => null,
        ]);
    }

    /**
     * Test validation error on missing required fields.
     */
    public function testStoreBusinessInfoValidationError()
    {
        // Create a test user
        $user = User::factory()->create();

        // Mock invalid request data (missing required fields)
        $data = [
            'business_type' => 'Retail',
            'currency' => 'USD',
            // Missing 'business_name' and 'reporting_year'
        ];

        // Act as the user and make a POST request
        $response = $this->actingAs($user, 'api')
            ->postJson('/api/v1/business-info', $data);

        // Assert the response
        $response->assertStatus(422);
        $response->assertJson([
            'code' => 422,
            'message' => 'Validation error',
            'data' => null,
            'error' => [
                'business_name' => ['The business name field is required.'],
                'reporting_year' => ['The reporting year field is required.'],
            ],
        ]);
    }

    /**
     * Test failure on unauthorized access.
     */
    public function testStoreBusinessInfoUnauthorized()
    {
        // Mock valid request data
        $data = [
            'business_name' => 'Test Business',
            'business_type' => 'Retail',
            'currency' => 'USD',
            'reporting_year' => 'January - December',
        ];

        // Make a POST request without authenticating a user
        $response = $this->postJson('/api/v1/business-info', $data);

        // Assert the response
        $response->assertStatus(401); // Unauthorized
    }

    public function testUpdateBusinessInfoSuccess()
    {
        $user = User::factory()->create();
        $businessInfo = BusinessInfo::factory()->create([
            'user_id' => $user->id,
        ]);

        $data = [
            'business_name' => 'Updated Business',
            'business_type' => 'Updated Retail',
            'currency' => 'USD',
            'reporting_year' => 'July - June',
        ];

        $response = $this->actingAs($user, 'api')
            ->putJson('/api/v1/business-info/' . $businessInfo->id, $data);

        $response->assertStatus(200);
        $response->assertJson([
            'code' => 200,
            'message' => 'Business info updated successfully',
            'data' => [
                'name' => 'Updated Business',
                'type' => 'Updated Retail',
            ],
        ]);
    }

    public function testUpdateBusinessInfoNotFound()
    {
        $user = User::factory()->create();

        $data = [
            'business_name' => 'Updated Business',
            'business_type' => 'Updated Retail',
            'currency' => 'USD',
            'reporting_year' => 'July - June',
        ];

        $nonExistentUuid = '00000000-0000-0000-0000-000000000000'; // Valid UUID format but non-existent

        $response = $this->actingAs($user, 'api')
            ->putJson('/api/v1/business-info/' . $nonExistentUuid, $data);

        $response->assertStatus(404);
        $response->assertJson([
            'code' => 404,
            'message' => 'Business info not found',
        ]);
    }

    public function testUpdateBusinessInfoValidationError()
    {
        $user = User::factory()->create();
        $businessInfo = BusinessInfo::factory()->create([
            'user_id' => $user->id,
        ]);

        $data = [
            // Missing required fields
            'business_name' => '',
            'business_type' => '',
        ];

        $response = $this->actingAs($user, 'api')
            ->putJson('/api/v1/business-info/' . $businessInfo->id, $data);

        $response->assertStatus(422);
        $response->assertJson([
            'code' => 422,
            'message' => 'Validation error',
        ]);
    }
}
