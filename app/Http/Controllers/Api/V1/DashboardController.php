<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\DashboardService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="Dashboard",
 *     description="Dashboard Analytics"
 * )
 */
class DashboardController extends Controller
{
    protected DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/dashboard",
     *     summary="Get monthly analytics for the authenticated user",
     *     tags={"Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Monthly analytics retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="status_code", type="number", example=200),
     *             @OA\Property(property="message", type="string", example="Successfully fetched dashboard analytics"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="total_bank_balance", type="number", format="float", example=10000.50),
     *                 @OA\Property(
     *                     property="expense",
     *                     type="object",
     *                     @OA\Property(property="this_month", type="number", format="float", example=5000.00),
     *                     @OA\Property(property="last_month", type="number", format="float", example=4500.00),
     *                     @OA\Property(property="difference", type="number", format="float", example=500.00),
     *                     @OA\Property(property="increased", type="boolean", example=true),
     *                     @OA\Property(property="decreased", type="boolean", example=false),
     *                 ),
     *                 @OA\Property(
     *                     property="income",
     *                     type="object",
     *                     @OA\Property(property="this_month", type="number", format="float", example=7000.00),
     *                     @OA\Property(property="last_month", type="number", format="float", example=7500.00),
     *                     @OA\Property(property="difference", type="number", format="float", example=-500.00),
     *                     @OA\Property(property="increased", type="boolean", example=false),
     *                     @OA\Property(property="decreased", type="boolean", example=true),
     *                 ),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */
    public function monthlyAnalytics(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'message' => 'Unauthorized',
                    'status' => 'error',
                    'status_code' => 401,
                    'data' => null
                ], 401);
            }

            $analytics = $this->dashboardService->getMonthlyAnalytics($user);

            return response()->json([
                'status' => 'success',
                'message' => "Successfully fetched analytics!",
                'status_code' => 200,
                'data' => $analytics,
            ]);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => "Failed to fetch analytics!",
                'status_code' => 500,
                'data' => null,
            ]);
        }
    }
}
