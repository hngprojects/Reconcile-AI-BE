<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerFeedbackRequest;
use App\Services\CustomerFeedback\CustomerFeedbackService;
use Illuminate\Http\JsonResponse;

class CustomerFeedbackController extends Controller
{
    protected $customerFeedbackService;

    public function __construct(CustomerFeedbackService $customerFeedbackService)
    {
        $this->customerFeedbackService = $customerFeedbackService;
    }

    /**
     * @OA\Post(
     *     path="/api/customer-feedback",
     *     summary="Submit customer feedback",
     *     description="Submits customer feedback and sends confirmation email",
     *     tags={"Feedback"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email"},
     *             @OA\Property(property="name", type="string", example="Mercy", description="Customer name"),
     *             @OA\Property(property="email", type="string", format="email", example="mercy@example.com", description="Customer email address"),
     *             @OA\Property(property="message", type="string", example="I enjoyed using the reconciliation platform", description="Feedback message"),
     *             @OA\Property(property="request_type", type="string", example="Feedback", description="Type of request")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Feedback submitted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Feedback Submitted Successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Mercy"),
     *                 @OA\Property(property="email", type="string", example="mercy@example.com"),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Feedback Submission Failed")
     *         )
     *     )
     * )
     */
    public function store(CustomerFeedbackRequest $request): JsonResponse
    {
        // $result = $this->customerFeedbackService->createCustomerFeedback($request);
        // return response()->json($result, $result->getCode());
        return $this->customerFeedbackService->createCustomerFeedback($request)->toJson();
    }
}
