<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ContactService;

class ContactController extends Controller
{
    protected $contactService;

    public function __construct(ContactService $contactService)
    {
        $this->contactService = $contactService;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/contact",
     *     summary="Submit a contact form",
     *     description="Handles the submission of a contact form. Validates the input and saves the message to the database.",
     *     tags={"Contact"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "message", "phone_number"},
     *             @OA\Property(property="name", type="string", example="John Doe", description="Full name of the user"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com", description="Email address of the user"),
     *             @OA\Property(property="message", type="string", example="Hello, this is a test message.", description="Message content"),
     *             @OA\Property(property="phone_number", type="string", example="1234567890", description="Phone number of the user")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Message saved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="status_code", type="integer", example=201),
     *             @OA\Property(property="message", type="string", example="Message saved successfully."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="message", type="string", example="Hello, this is a test message."),
     *                 @OA\Property(property="phone_number", type="string", example="1234567890")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="status_code", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="Validation failed."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="name", type="string", example="The name field is required."),
     *                 @OA\Property(property="email", type="string", example="The email field is required."),
     *                 @OA\Property(property="message", type="string", example="The message field is required."),
     *                 @OA\Property(property="phone_number", type="string", example="The phone number field is required.")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="status_code", type="integer", example=500),
     *             @OA\Property(property="message", type="string", example="Failed to save message."),
     *             @OA\Property(property="data", type="null", example=null)
     *         )
     *     )
     * )
     */
    public function contact(Request $request)
    {
        $data = $request->only(['name', 'email', 'message', 'phone_number']);

        // Validate input
        [$isValid, $validationMessage] = $this->contactService->validateInput($data);
        if (!$isValid) {
            return response()->json([
                'status' => 'error',
                'status_code' => 400,
                'message' => 'Validation failed.',
                'errors' => $validationMessage // Include validation errors
            ], 400);
        }

        // Save the message
        [$success, $serviceMessage] = $this->contactService->saveContactMessage($data);
        if (!$success) {
            return response()->json([
                'status' => 'error',
                'status_code' => 500,
                'message' => $serviceMessage,
                'data' => null
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'status_code' => 201,
            'message' => $serviceMessage,
            'data' => $data // Include the saved data
        ], 201);
    }
}
