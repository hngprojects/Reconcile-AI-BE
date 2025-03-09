<?php

namespace App\Services;

use App\Models\ContactSubmission;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactMessage;

class ContactService
{
    /**
     * Validate the input data.
     *
     * @param array $data
     * @return array
     */
    public function validateInput(array $data): array
    {
        $errors = [];

        // Check if required fields are missing
        if (empty($data['name'])) {
            $errors['name'] = 'The name field is required.';
        }
        if (empty($data['email'])) {
            $errors['email'] = 'The email field is required.';
        }
        if (empty($data['message'])) {
            $errors['message'] = 'The message field is required.';
        }
        if (empty($data['phone_number'])) {
            $errors['phone_number'] = 'The phone number field is required.';
        }

        // Validate email format if the email field is not empty
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'The email must be a valid email address.';
        }

        // If there are errors, return them
        if (!empty($errors)) {
            return [false, $errors];
        }

        return [true, 'Input is valid.'];
    }

    /**
     * Save the contact message.
     *
     * @param array $data
     * @return array
     */
    public function saveContactMessage(array $data): array
    {
        try {

            ContactSubmission::create($data);

            return [true, 'Message saved successfully.'];
        } catch (\Exception $e) {
            return [false, 'Failed to save message.'];
        }
    }
}
