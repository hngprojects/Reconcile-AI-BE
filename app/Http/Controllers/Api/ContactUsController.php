<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactUs\ContactFormRequest;
use App\Services\ContactUs\ContactUsService;
use Illuminate\Http\Request;

class ContactUsController extends Controller
{
    private ContactUsService $contactUs;

    public function __construct(ContactUsService $contactUs)
    {
        $this->contactUs = $contactUs;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/contact/contact-us",
     *     summary="Send a contact message",
     *     tags={"Contact-Us"},
     *     description="Allows users to send a contact message.",
     *     operationId="saveContactMessage",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "message"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="johndoe@example.com"),
     *             @OA\Property(property="message", type="string", example="I would like to inquire about your services.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Contact message sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Contact message sent Successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Failed to send contact message",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="Contact message sent Failed"),
     *             @OA\Property(property="error", type="string", example="Unexpected error occurred")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The email field is required."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="email", type="array",
     *                     @OA\Items(type="string", example="The email must be a valid email address.")
     *                 ),
     *                 @OA\Property(property="name", type="array",
     *                     @OA\Items(type="string", example="The name field is required.")
     *                 ),
     *                 @OA\Property(property="message", type="array",
     *                     @OA\Items(type="string", example="The message field is required.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function saveContactMessage(ContactFormRequest $request)
    {
        return $this->contactUs->saveContactMessage($request)->toJson();
    }
}
