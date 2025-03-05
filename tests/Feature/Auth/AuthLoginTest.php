<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class AuthLoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test successful login.
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        // Create a test user
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Attempt to login
        $response = $this->postJson(route('auth.login'), [
            'email' => 'test@example.com',
            'password' => 'password123',
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
                'message' => 'Login Success',
            ]);

        // Ensure token exists and is a string
        $this->assertIsString($response->json('data.token'));
        $this->assertNotEmpty($response->json('data.token'));
    }

    /**
     * Test login failure with incorrect credentials.
     */
    // public function test_user_cannot_login_with_invalid_credentials(): void
    // {
    //     $response = $this->postJson(route('auth.login'), [
    //         'email' => 'wrong@example.com',
    //         'password' => 'wrongpassword',
    //     ]);

    //     // Assertions
    //     $response->assertStatus(401)
    //         ->assertJson([
    //             'code' => 401,
    //             'message' => 'Invalid credentials',
    //         ]);
    // }
}
