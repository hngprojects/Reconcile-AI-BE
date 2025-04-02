<?php

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

class UpdateProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public'); // Fake storage for testing file uploads
    }

    protected function tearDown(): void
    {
        Storage::fake('public'); // Ensure fake storage is cleared
        parent::tearDown();
    }

    public function test_user_can_update_profile_without_avatar()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/profile/update', [
            'country' => 'United States',
            'city' => 'New York',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'success',
                     'status_code' => 200,
                     'message' => 'Profile updated successfully',
                 ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'country' => 'United States',
            'city' => 'New York',
        ]);
    }

    public function test_user_can_update_profile_with_avatar()
    {
        $user = User::factory()->create();

        $avatar = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->actingAs($user)->postJson('/api/v1/profile/update', [
            'avatar' => $avatar,
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'success',
                     'status_code' => 200,
                     'message' => 'Profile updated successfully',
                 ]);

        $user->refresh();
        Storage::disk('public')->assertExists($user->avatar);
    }

    public function test_validation_fails_for_invalid_avatar()
    {
        $user = User::factory()->create();

        $invalidFile = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($user)->postJson('/api/v1/profile/update', [
            'avatar' => $invalidFile,
        ]);

        $response->assertStatus(400)
                 ->assertJson([
                     'status' => 'error',
                     'status_code' => 400,
                     'message' => 'Validation failed',
                 ]);

        Storage::disk('public')->assertMissing('avatars/document.pdf');
    }

    public function test_unauthenticated_user_cannot_update_profile()
    {
        $response = $this->postJson('/api/v1/profile/update', [
            'country' => 'United States',
        ]);

        $response->assertStatus(401)
                 ->assertJson([
                     'message' => 'Unauthenticated.',
                 ]);
    }
}
