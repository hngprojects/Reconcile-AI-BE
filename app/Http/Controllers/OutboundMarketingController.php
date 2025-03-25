<?php

namespace App\Http\Controllers;

use App\Models\OutboundMarketing;
use App\Services\OutboundMarketingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OutboundMarketingController extends Controller
{
    protected $marketingService;

    public function __construct(OutboundMarketingService $marketingService)
    {
        $this->marketingService = $marketingService;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/outbound-marketing",
     *     summary="Create a new outbound marketing campaign",
     *     description="Creates a new marketing campaign with the provided details",
     *     tags={"Marketing"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"full_name", "business_name", "email", "phone_number"},
     *             @OA\Property(property="full_name", type="string", example="John Smith", description="Full name of the contact"),
     *             @OA\Property(property="business_name", type="string", example="Acme Corp", description="Name of the business"),
     *             @OA\Property(property="email", type="string", format="email", example="john@acmecorp.com", description="Contact email address"),
     *             @OA\Property(property="phone_number", type="string", example="1234567890", description="Contact phone number")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Marketing campaign created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Marketing campaign created successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="full_name", type="string", example="John Smith"),
     *                 @OA\Property(property="business_name", type="string", example="Acme Corp"),
     *                 @OA\Property(property="email", type="string", example="john@acmecorp.com"),
     *                 @OA\Property(property="phone_number", type="string", example="1234567890"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="The given data was invalid")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Internal server error")
     *         )
     *     )
     * )
     */

    public function store(Request $request): JsonResponse
    {
        [$isValid, $validationResult] = $this->marketingService->validateInput($request->all());

        if (!$isValid) {
            $statusCode = ($validationResult === 'The email address is already in use.') ? 409 : 400;

            return response()->json([
                'status' => 'error',
                'status_code' => $statusCode,
                'message' => $validationResult,
                'errors' => $statusCode === 400 ? $validationResult : null
            ], $statusCode);
        }

        [$success, $result] = $this->marketingService->createOutboundMarketing($validationResult);

        if (!$success) {
            return response()->json([
                'status' => 'error',
                'status_code' => 500,
                'message' => $result,
                'data' => null
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'status_code' => 201,
            'message' => 'Marketing campaign created successfully',
            'data' => $result
        ], 201);
    }
}
