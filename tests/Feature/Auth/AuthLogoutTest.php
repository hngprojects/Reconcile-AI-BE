<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthLogoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test successful logout.
     */
    public function test_user_can_logout(): void
    {
        // Create a user
        $user = User::factory()->create();

        // Generate a JWT token for the user
        $token = JWTAuth::fromUser($user);

        // Send a POST request to logout with the Authorization header
        $response = $this->postJson(route('auth.logout'), [], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert response status is 200
        $response->assertStatus(200)
            ->assertJson([
                'code' => 200,
                'message' => 'Logout Success',
            ]);
    }
}
