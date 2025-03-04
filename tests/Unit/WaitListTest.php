<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Waitlist;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WaitListTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_requires_an_email()
    {
        $response = $this->postJson('/api/v1/wait-list', []);

        $response->assertStatus(400)
            ->assertJson([
                'status' => false,
                'message' => 'Email is required'
            ]);
    }

    /** @test */
    public function it_validates_email_format()
    {
        $response = $this->postJson('/api/v1/wait-list', [
            'email' => 'not-an-email'
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'status' => false,
                'message' => 'Please provide a valid email'
            ]);
    }

    /** @test */
    public function it_prevents_duplicate_emails()
    {
        // Create initial waitlist entry
        Waitlist::create(['email' => 'test@example.com']);

        $response = $this->postJson('/api/v1/wait-list', [
            'email' => 'test@example.com'
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'status' => false,
                'message' => 'Email already registered'
            ]);
    }

    /** @test */
    public function it_successfully_adds_email_to_waitlist()
    {
        $response = $this->postJson('/api/v1/wait-list', [
            'email' => 'new@example.com'
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => true,
                'message' => 'Successfully joined wait list'
            ]);

        $this->assertDatabaseHas('waitlist', [
            'email' => 'new@example.com'
        ]);
    }
}
