<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\ContactService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;

class ContactControllerTest extends TestCase
{
    use RefreshDatabase; // Refresh the database after each test

    /**
     * Test validation failure when required fields are missing.
     *
     * @return void
     */
    public function test_validation_failure_when_required_fields_are_missing()
    {
        // Mock the ContactService to return validation errors
        $contactServiceMock = $this->mock(ContactService::class, function ($mock) {
            $mock->shouldReceive('validateInput')
                ->andReturn([false, [
                    'name' => 'The name field is required.',
                    'email' => 'The email field is required.',
                    'message' => 'The message field is required.'
                ]]);
        });

        // Make a POST request with empty data
        $response = $this->postJson('/api/v1/contact', []);

        // Assert the response
        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJson([
                'status' => 'error',
                'status_code' => 400,
                'message' => 'Validation failed.',
                'data' => [
                    'name' => 'The name field is required.',
                    'email' => 'The email field is required.',
                    'message' => 'The message field is required.'
                ]
            ]);
    }

    /**
     * Test validation failure when email is invalid.
     *
     * @return void
     */
    public function test_validation_failure_when_email_is_invalid()
    {
        // Mock the ContactService to return validation errors
        $contactServiceMock = $this->mock(ContactService::class, function ($mock) {
            $mock->shouldReceive('validateInput')
                ->andReturn([false, [
                    'email' => 'The email must be a valid email address.'
                ]]);
        });

        // Make a POST request with invalid email
        $response = $this->postJson('/api/v1/contact', [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'message' => 'Hello'
        ]);

        // Assert the response
        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJson([
                'status' => 'error',
                'status_code' => 400,
                'message' => 'Validation failed.',
                'data' => [
                    'email' => 'The email must be a valid email address.'
                ]
            ]);
    }

    /**
     * Test successful message submission.
     *
     * @return void
     */
    public function test_successful_message_submission()
    {
        // Mock the ContactService to return success
        $contactServiceMock = $this->mock(ContactService::class, function ($mock) {
            $mock->shouldReceive('validateInput')
                ->andReturn([true, 'Input is valid.']);
            $mock->shouldReceive('saveContactMessage')
                ->andReturn([true, 'Message saved successfully.']);
        });

        // Make a POST request with valid data
        $response = $this->postJson('/api/v1/contact', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Hello, this is a test message.'
        ]);

        // Assert the response
        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJson([
                'status' => 'success',
                'status_code' => 201,
                'message' => 'Message saved successfully.',
                'data' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                    'message' => 'Hello, this is a test message.'
                ]
            ]);
    }

    /**
     * Test server error when saving the message fails.
     *
     * @return void
     */
    public function test_server_error_when_saving_message_fails()
    {
        // Mock the ContactService to return a server error
        $contactServiceMock = $this->mock(ContactService::class, function ($mock) {
            $mock->shouldReceive('validateInput')
                ->andReturn([true, 'Input is valid.']);
            $mock->shouldReceive('saveContactMessage')
                ->andReturn([false, 'Failed to save message.']);
        });

        // Make a POST request with valid data
        $response = $this->postJson('/api/v1/contact', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Hello, this is a test message.'
        ]);

        // Assert the response
        $response->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR)
            ->assertJson([
                'status' => 'error',
                'status_code' => 500,
                'message' => 'Failed to save message.',
                'data' => null
            ]);
    }
}
