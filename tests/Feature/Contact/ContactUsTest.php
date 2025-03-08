<?php

namespace Tests\Feature\Contact;

use Tests\TestCase;

class ContactUsTest extends TestCase
{
    /**
     * Contact Us Test.
     */
    public function test_it_can_save_contact_message(): void
    {
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'message' => 'This is a test contact message',
        ];

        $response = $this->postJson(route('contact.contact-us'), $data);

        $response->assertStatus(200)
            ->assertJson([
                'code' => 200,
                'message' => 'Contact message sent Successful',
            ]);

        $this->assertDatabaseHas('contact_submissions', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'message' => 'This is a test contact message',
        ]);

    }
}
