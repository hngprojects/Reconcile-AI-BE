<?php

namespace Tests\Feature\NewsLetter;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\NewsLetter;

class NewsLetterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_subscribe_to_newsletter()
    {
        $data = ['email' => 'test@example.com'];

        $response = $this->postJson(route('newsletter.subscribe'), $data);

        $response->assertStatus(200)
            ->assertJson([
                'code' => 200,
                'message' => 'Subscription Successful',
                'data' => [
                    'email' => 'test@example.com',
                ],
            ]);

        $this->assertDatabaseHas('news_letters', [
            'email' => 'test@example.com',
            'subscribed' => true,
        ]);
    }
    
    public function test_it_cannot_subscribe_without_email()
    {
        $data = [];

        $response = $this->postJson(route('newsletter.subscribe'), $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_it_cannot_subscribe_with_the_same_email_twice()
    {
        $data = ['email' => 'duplicate@example.com'];
        $this->postJson(route('newsletter.subscribe'), $data)->assertStatus(200);

        $this->postJson(route('newsletter.subscribe'), $data)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertDatabaseHas('news_letters', [
            'email' => 'duplicate@example.com',
            'subscribed' => true,
        ]);
    }

    public function test_it_can_unsubscribe_from_newsletter()
    {
        $newsletter = NewsLetter::factory()->create([
            'email' => 'test@example.com',
            'subscribed' => true,
        ]);

        $data = ['email' => 'test@example.com'];

        $response = $this->postJson(route('newsletter.unsubscribe'), $data);

        $response->assertStatus(200)
            ->assertJson([
                'code' => 200,
                'message' => 'Unsubscribed Successfully',
                'data' => [
                    'email' => 'test@example.com',
                ],
            ]);

        $this->assertDatabaseHas('news_letters', [
            'email' => 'test@example.com',
            'subscribed' => false,
        ]);
    }
    
    public function test_it_cannot_unsubscribe_email_that_does_not_exist()
    {
        $data = ['email' => 'notfound@example.com'];

        $response = $this->postJson(route('newsletter.unsubscribe'), $data);

        $response->assertStatus(400)
            ->assertJson([
                'code' => 400,
                'message' => 'Unsubscription Failed',
            ]);
    }

    public function test_user_cannot_subscribe_to_newsletter_with_same_email_twice()
    {
        $data = ['email' => 'duplicate@example.com'];
        $this->postJson(route('newsletter.subscribe'), $data)->assertStatus(200);
        $this->postJson(route('newsletter.subscribe'), $data)->assertStatus(422);
    }
}
