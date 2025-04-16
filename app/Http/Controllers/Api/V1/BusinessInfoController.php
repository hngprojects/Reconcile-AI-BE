<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\BusinessInfo\BusinessInfoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Business",
 *     description="Business Information Management"
 * )
 */
class BusinessInfoController extends Controller
{
    /**
     * @OA\Schema(
     *     schema="BusinessInfoRequest",
     *     required={"business_name", "business_type", "currency", "reporting_year"},
     *     @OA\Property(property="business_name", type="string", example="My Business"),
     *     @OA\Property(property="business_type", type="string", example="Retail"),
     *     @OA\Property(property="currency", type="string", example="NGN"),
     *     @OA\Property(
     *         property="reporting_year", 
     *         type="string", 
     *         enum={"January - December", "April - March", "July - June"}, 
     *         example="January - December"
     *     )
     * )
     *
     * @OA\Schema(
     *     schema="BusinessInfoResponse",
     *     @OA\Property(property="code", type="integer", example=201),
     *     @OA\Property(property="message", type="string", example="Business info created successfully"),
     *     @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/BusinessInfo"
     *     )
     * )
     *
     * @OA\Schema(
     *     schema="BusinessInfo",
     *     @OA\Property(property="id", type="string", format="uuid"),
     *     @OA\Property(property="user_id", type="integer"),
     *     @OA\Property(property="name", type="string"),
     *     @OA\Property(property="type", type="string"),
     *     @OA\Property(property="currency", type="string"),
     *     @OA\Property(property="reporting_year", type="string"),
     *     @OA\Property(property="created_at", type="string", format="date-time"),
     *     @OA\Property(property="updated_at", type="string", format="date-time")
     * )
     * 
     * @OA\Schema(
     *     schema="ValidationErrorResponse",
     *     @OA\Property(property="code", type="integer", example=422),
     *     @OA\Property(property="message", type="string", example="Validation error"),
     *     @OA\Property(
     *         property="error",
     *         type="object",
     *         @OA\AdditionalProperties(type="array", @OA\Items(type="string"))
     *     )
     * )
     *
     * @OA\Schema(
     *     schema="ServerErrorResponse",
     *     @OA\Property(property="code", type="integer", example=500),
     *     @OA\Property(property="message", type="string", example="Server error"),
     *     @OA\Property(property="error", type="string", example="An unexpected error occurred")
     * )
    */
    public function __construct(
        private BusinessInfoService $businessInfoService
    ) {}

    /**
     * @OA\Post(
     *     path="/api/v1/business-info",
     *     summary="Create business information",
     *     tags={"Business"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/BusinessInfoRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Business info created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/BusinessInfoResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse")
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $result = $this->businessInfoService->setupBusinessInfo($request);
        return response()->json($result, $result['code']);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/business-info/{id}",
     *     summary="Update business information",
     *     tags={"Business"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Business info ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/BusinessInfoRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Business info updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/BusinessInfoResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Business info not found",
     *         @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse")
     *     )
     * )
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $result = $this->businessInfoService->updateBusinessInfo($id, $request);
        return response()->json($result, $result['code']);
    }
}
