<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthPasswordResetTest extends TestCase
{
    use RefreshDatabase;
    /**
     * send password reset link
     * @return void
     */
    public function test_user_can_request_password_reset_link()
    {
        $user = User::factory()->create(['email' => 'user@example.com']);

        $response = $this->postJson(route('auth.forgot-password'), [
            'email' => $user->email,
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Password reset link sent.']);
    }

    public function test_user_cannot_request_password_reset_link_with_invalid_email()
    {
        $response = $this->postJson(route('auth.forgot-password'), [
            'email' => '
            invalid-email',
        ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_user_cannot_request_password_reset_link_without_email()
    {
        $response = $this->postJson(route('auth.forgot-password'));
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * reset user password
     * @return void
     */
    public function test_user_can_reset_password()
    {
        $user = User::factory()->create(['email' => 'user@example.com']);

        $token = Password::createToken($user);

        $response = $this->postJson(route('auth.reset-password'), [
            'email' => $user->email,
            'token' => $token,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Password has been reset. Return to Login page to continue.']);

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }
}
