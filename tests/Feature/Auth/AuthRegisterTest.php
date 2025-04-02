<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuthRegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test successful register.
     */
    public function test_user_can_register_with_valid_credentials(): void
    {
        // Attempt to register
        $response = $this->postJson(route('auth.register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Assertions
        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'email_verified_at',
                        'created_at',
                        'updated_at',
                    ],
                    'token'
                ]
            ])
            ->assertJson([
                'code' => 200,
                'message' => 'User account registration successful',
            ]);

        // Ensure token exists and is a string
        $this->assertIsString($response->json('data.token'));
        $this->assertNotEmpty($response->json('data.token'));
    }

    /**
     * Test register failure with missing credentials.
     */
    public function test_user_cannot_register_with_missing_credentials(): void
    {
        $response = $this->postJson(route('auth.register'), []);

        // Assertions
        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'name',
                    'email',
                    'password',
                ],
            ]);
    }
}
