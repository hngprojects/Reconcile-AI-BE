<?php

namespace App\Http\Controllers;

use App\Models\ChartAccountCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountChartCategoryController extends Controller
{

    /**
     * Get all chart account categories
     *
     * @OA\Get(
     *     path="/api/v1/chart-account-categories",
     *     summary="Get all chart account categories",
     *     description="Retrieves a list of all chart account categories",
     *     tags={"Chart Account Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         description="Filter categories by name",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Chart account categories retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Chart account categories retrieved successfully"),
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Category Name"),
     *                     @OA\Property(property="is_active", type="boolean", example=true),
     *                     @OA\Property(property="is_required", type="boolean", example=false),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T00:00:00.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-01T00:00:00.000000Z")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     )
     * )
     */
    public function index(Request $request)

    {
        $user =  Auth::user();
        if (!$user) {
            // return json_response("Unauthorized", 401);
            return response()->json([
                'message' => 'Unauthorized',
                'status' => 'error',
                'status_code' => 401,
                'data' => null
            ], 401);
        }

        $query = ChartAccountCategory::query();

        // Filter by name if provided
        if ($request->has('name')) {
            $query->where('title', 'ILIKE', '%' . $request->name . '%');
        }

        $categories = $query->get();
        return response()->json([
            'message' => 'Chart account categories retrieved successfully',
            'status' => 'success',
            'status_code' => 200,
            'data' => $categories
        ], 200);
    }

    /**
     * Toggle a chart account category
     *
     * @OA\Put(
     *     path="/api/v1/chart-account-categories/{id}/toggle",
     *     summary="Toggle a chart account category",
     *     description="Toggles the active status of a chart account category",
     *     tags={"Chart Account Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the chart account category to toggle",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Chart account category updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Chart account category updated successfully"),
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", example="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"),
     *                 @OA\Property(property="name", type="string", example="Category Name"),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="is_required", type="boolean", example=false),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T00:00:00.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-01T00:00:00.000000Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Category is required and cannot be disabled",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Category is required and cannot be disabled"),
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="status_code", type="integer", example=403),
     *             @OA\Property(property="data", type="null", example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     )
     * )
     */
    public function toggle_category(Request $request)
    {
        $user =  Auth::user();
        if (!$user) {
            // return json_response("Unauthorized", 401);
            return response()->json([
                'message' => 'Unauthorized',
                'status' => 'error',
                'status_code' => 401,
                'data' => null
            ], 401);
        }
        $category = ChartAccountCategory::find($request->id);
        // check if the category is required 
        if ($category->is_required) {
            return response()->json([
                'message' => 'This category is required and can not be disabled',
                'status' => 'error',
                'status_code' => 403,
                'data' => null
            ], 403);
        }
        if (!$category) {
            return response()->json([
                'message' => 'Chart account category not found',
                'status' => 'error',
                'status_code' => 404,
                'data' => null
            ], 404);
        }


        $category->is_active = !$category->is_active;
        $category->save();
        return response()->json([
            'message' => 'Chart account category updated successfully',
            'status' => 'success',
            'status_code' => 200,
            'data' => $category
        ], 200);
    }
}
