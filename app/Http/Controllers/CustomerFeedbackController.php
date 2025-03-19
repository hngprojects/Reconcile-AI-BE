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
     *     path="/api/v1/customer-feedback",
     *     summary="Submit customer feedback",
     *     description="Submits customer feedback with an optional file attachment",
     *     tags={"Feedback"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name", "email", "message", "request_type"},
     *                 @OA\Property(property="name", type="string", example="Mercy", description="Customer name"),
     *                 @OA\Property(property="email", type="string", format="email", example="tulbadex@gmail.com", description="Customer email address"),
     *                 @OA\Property(property="subject", type="string", example="Product Feedback", description="Subject of the feedback"),
     *                 @OA\Property(property="message", type="string", example="I enjoyed using the reconciliation platform", description="Feedback message"),
     *                 @OA\Property(property="file", type="string", format="binary", description="Optional file attachment"),
     *                 @OA\Property(property="request_type", type="string", example="Feedback", description="Type of request")
     *             )
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
     *                 @OA\Property(property="email", type="string", example="tulbadex@gmail.com"),
     *                 @OA\Property(property="subject", type="string", example="Product Feedback"),
     *                 @OA\Property(property="file_path", type="string", example="/storage/feedback_attachments/1647782345_document.pdf"),
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
        return $this->customerFeedbackService->createCustomerFeedback($request)->toJson();
    }
}