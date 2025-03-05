<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test; // Import the Test attribute

class ForgotResetPasswordControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    #[Test]
    public function forgot_password_fails_with_invalid_email()
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'invalid-email'
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => [
                    'email' => ['The email field must be a valid email address.']
                ],
                'data' => null
            ]);
    }

    #[Test]
    public function forgot_password_fails_with_nonexistent_email()
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'nonexistent@example.com'
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Account with the specified email doesn\'t exist',
                'data' => null
            ]);
    }

    #[Test]
    public function forgot_password_sends_reset_link_successfully()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com'
        ]);

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'test@example.com'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Password reset link sent successfully',
                'data' => null
            ]);

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'test@example.com'
        ]);

        Mail::assertSent(\App\Mail\PasswordResetMail::class);
    }
}
