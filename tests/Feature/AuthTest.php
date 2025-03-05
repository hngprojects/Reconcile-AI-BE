<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuthTest extends TestCase
{
    /**
     * A basic feature test example.
     */

    use RefreshDatabase;

     public function test_signup_and_get_a_token(): void
    {
        $response = $this->postJson('/api/v1/auth/sign-up', [
            'name' => 'Mark Cyril',
            'email' => 'test@example.com',
            'password' => 'password1234',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                    'status_code',
                    'message',
                    'access_token'
                 ]);
    }

    public function test_invalid_signup_fails_and_cannot_get_a_token(): void
    {
        $response = $this->postJson('/api/v1/auth/sign-up', [
            //the 'nam is intentional to simulate bad request
            'nam' => 'Mark Cyril',
            'password' => 'password1234',
        ]);

        $response->assertStatus(422)
                 ->assertJsonStructure([
                    'status_code',
                    'message',
                    'errors'
                 ])
                 ->assertJsonValidationErrors(['email']);;
    }

    public function test_signup_fails_for_existing_users(): void
    {
        User::factory()->create([
            'email' => 'test@example.com'
        ]);

        $response = $this->postJson('/api/v1/auth/sign-up', [
            'email' => 'test@example.com',
            'name' => 'Mark Angel',
            'password' => '12345654321'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }
}
