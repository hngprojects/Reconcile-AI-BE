<?php

namespace App\Http\Controllers;

use App\Models\ChartAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChartOfAccountController extends Controller
{
    /**
     * Create a new chart account
     *
     * @OA\Post(
     *     path="/api/v1/chart-accounts",
     *     summary="Create a new chart account",
     *     description="Creates a new chart account for the authenticated user",
     *     tags={"Chart Accounts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"account_number", "account_name", "balance", "account_chart_category_id"},
     *             @OA\Property(property="account_number", type="string", example="1234567890", description="Account number"),
     *             @OA\Property(property="account_name", type="string", example="Operating Expenses", description="Name of the account"),
     *             @OA\Property(property="balance", type="number", format="float", example=1000.00, description="Account balance"),
     *             @OA\Property(property="description", type="string", example="For tracking operating expenses", description="Account description"),
     *             @OA\Property(property="user_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="User ID"),
     *             @OA\Property(property="account_chart_category_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440001", description="Chart category ID")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Chart account created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Chart account created successfully"),
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="status_code", type="integer", example=201),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="user_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="account_number", type="string", example="1234567890"),
     *                 @OA\Property(property="account_name", type="string", example="Operating Expenses"),
     *                 @OA\Property(property="balance", type="number", format="float", example=1000.00),
     *                 @OA\Property(property="description", type="string", example="For tracking operating expenses"),
     *                 @OA\Property(property="account_chart_category_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440001"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T00:00:00.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-01T00:00:00.000000Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="account_number",
     *                     type="array",
     *                     @OA\Items(type="string", example="The account number field is required.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'account_number' => 'required|string|max:50',
            'account_name' => 'required|string|max:255',
            'balance' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255',
            'user_id' => 'required|uuid|string',
            'account_chart_category_id' => 'required|string|uuid',
        ]);

        // Add user_id to the validated data
        $validated['user_id'] = Auth::id();


        $bankAccount = ChartAccount::create($validated);

        return response()->json([
            'message' => 'Chart account created successfully',
            'status' => 'success',
            'status_code' => 201,
            'data' => $bankAccount
        ], 201);
    }
}
