<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use App\Mail\WelcomeEmail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    protected $googlePayload = [
        'sub' => '1234567890',
        'email' => 'test@example.com',
        'given_name' => 'John',
        'family_name' => 'Doe',
        'picture' => 'https://example.com/avatar.jpg'
    ];

    #[Test]
    public function it_shows_is_new_user_in_user_array()
    {
        Http::fake([
            'www.googleapis.com/*' => Http::response($this->googlePayload)
        ]);

        $response = $this->postJson('/api/v1/auth/google-login', [
            'id_token' => 'valid_token'
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.user.is_new_user', true)
            ->assertJsonStructure([
                'data' => [
                    'user' => [
                        'id',
                        'email',
                        'name',
                        'avatar',
                        'is_new_user'  // Verify it's in user array
                    ]
                ]
            ]);
    }

    #[Test]
    public function it_logs_in_existing_user_with_valid_google_token()
    {
        $user = User::factory()->create(['email' => $this->googlePayload['email']]);
        
        Http::fake([
            'www.googleapis.com/*' => Http::response($this->googlePayload)
        ]);

        $response = $this->postJson('/api/v1/auth/google-login', [
            'id_token' => 'valid_token'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status_code' => 200,
                'message' => 'Login Successful',
                'data' => [
                    'user' => [
                        'is_new_user' => false
                    ]
                ]
            ]);
    }

    #[Test]
    public function it_registers_new_user_with_valid_google_token()
    {
        Mail::fake();
        
        Http::fake([
            'www.googleapis.com/*' => Http::response($this->googlePayload)
        ]);

        $response = $this->postJson('/api/v1/auth/google-login', [
            'id_token' => 'valid_token'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status_code' => 200,
                'message' => 'User Created Successfully',
                'data' => [
                    'user' => [
                        'is_new_user' => true
                    ]
                ]
            ]);

        $this->assertDatabaseHas('users', ['email' => $this->googlePayload['email']]);
        Mail::assertQueued(WelcomeEmail::class);
    }

    #[Test]
    public function it_rejects_invalid_google_token()
    {
        Http::fake([
            'www.googleapis.com/*' => Http::response([], 401)
        ]);

        $response = $this->postJson('/api/v1/auth/google-login', [
            'id_token' => 'invalid_token'
        ]);

        $response->assertStatus(401);
    }
}
