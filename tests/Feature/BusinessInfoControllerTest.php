<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

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
}
