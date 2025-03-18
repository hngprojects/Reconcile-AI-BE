<?php

namespace App\Http\Controllers;

use App\Mail\PartnerWelcomeMail;
use App\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class PartnerController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/partners",
     *     summary="Submit a partnership request",
     *     description="Submits a partnership request form with contact and business details",
     *     tags={"Partners"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"full_name", "service_interested", "email", "phone_number"},
     *             @OA\Property(property="full_name", type="string", example="John Smith", description="Full name of the partner"),
     *             @OA\Property(property="business_name", type="string", example="Acme Corp", description="Name of the business (optional)"),
     *             @OA\Property(property="service_interested", type="string", example="Data reconciliation services", description="Services the partner is interested in"),
     *             @OA\Property(property="email", type="string", format="email", example="john@acmecorp.com", description="Partner email address"),
     *             @OA\Property(property="phone_number", type="string", example="1234567890", description="Partner phone number")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Partnership request submitted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Thank you for your submission. We will contact you shortly.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="full_name", type="array", @OA\Items(type="string", example="The full name field is required.")),
     *                 @OA\Property(property="service_interested", type="array", @OA\Items(type="string", example="The service interested field is required.")),
     *                 @OA\Property(property="email", type="array", @OA\Items(type="string", example="The email must be a valid email address.")),
     *                 @OA\Property(property="phone_number", type="array", @OA\Items(type="string", example="The phone number field is required."))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Internal server error")
     *         )
     *     )
     * )
     * 
     * Handle the contact form submission
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function submit(Request $request)
    {
        // Validate the form data
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'business_name' => 'nullable|string|max:255',
            'service_interested' => 'required|string|max:500',
            'email' => 'required|email|max:255',
            'phone_number' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Store the contact submission
        $submission = Partner::create([
            'full_name' => $request->full_name,
            'business_name' => $request->business_name,
            'service_interested' => $request->service_interested,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
        ]);

        // Send notification email
        try {
            Mail::to(config('mail.admin_address'))->send(new PartnerWelcomeMail($submission));
        } catch (\Exception $e) {
            // Log the error but don't fail the request
            Log::error('Failed to send contact form email: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Thank you for your submission. We will contact you shortly.'
        ], 200);
    }
}
