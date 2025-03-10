<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class UserTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_authenticated_user_can_fetch_profile()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/v1/user');

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'status',
            'status_code',
            'message',
            'data' => [
                'user' => ['id', 'name', 'email']
            ]
        ]);

        $response->assertJson([
            'status' => 'success',
            'status_code' => 200,
            'message' => 'User successfully fetched',
        ]);

        $response->assertJsonFragment([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }
}
