<?php

namespace App\Http\Controllers;

use App\Models\ChartAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

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
        $validator = Validator::make($request->all(), [
            'account_number' => 'required|string|max:50',
            'account_name' => 'required|string|max:255',
            'balance' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255',
            'account_chart_category_id' => 'required|string|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'status' => 'error',
                'status_code' => 422,
                'errors' => $validator->errors()
            ], 422);
        }

        // Add user_id to the validated data
        $validated = $validator->validated();
        $validated['user_id'] = Auth::id();


        $bankAccount = ChartAccount::create($validated);

        return response()->json([
            'message' => 'Chart account created successfully',
            'status' => 'success',
            'status_code' => 201,
            'data' => $bankAccount
        ], 201);
    }

    /**
     * Update a chart account
     *
     * @OA\Patch(
     *     path="/api/v1/chart-accounts/{id}",
     *     summary="Update a chart account",
     *     description="Updates specific fields of an existing chart account",
     *     tags={"Chart Accounts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Chart account ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="account_number", type="string", example="1234567890"),
     *             @OA\Property(property="account_name", type="string", example="Operating Expenses"),
     *             @OA\Property(property="balance", type="number", format="float", example=1000.00),
     *             @OA\Property(property="description", type="string", example="For tracking operating expenses"),
     *             @OA\Property(property="account_chart_category_id", type="string", format="uuid")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Chart account updated successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Chart account not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'account_number' => 'sometimes|required|string|max:50',
            'account_name' => 'sometimes|required|string|max:255',
            'balance' => 'sometimes|required|numeric|min:0',
            'description' => 'sometimes|nullable|string|max:255',
            'account_chart_category_id' => 'sometimes|required|string|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'status' => 'error',
                'status_code' => 422,
                'errors' => $validator->errors()
            ], 422);
        }

        $account = ChartAccount::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$account) {
            return response()->json([
                'message' => 'Chart account not found',
                'status' => 'error',
                'status_code' => 404,
                'data' => null
            ], 404);
        }

        $validated = $validator->validated();
        $account->update($validated);

        return response()->json([
            'message' => 'Chart account updated successfully',
            'status' => 'success',
            'status_code' => 200,
            'data' => $account
        ], 200);
    }

    /**
     * Get all chart accounts for authenticated user
     *
     * @OA\Get(
     *     path="/api/v1/chart-accounts",
     *     summary="Get all chart accounts",
     *     description="Returns all chart accounts for the authenticated user",
     *     tags={"Chart Accounts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="category_name",
     *         in="query",
     *         description="Filter categories by title",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of chart accounts",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Chart accounts retrieved successfully"),
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="account_number", type="string"),
     *                     @OA\Property(property="account_name", type="string"),
     *                     @OA\Property(property="balance", type="number", format="float"),
     *                     @OA\Property(property="description", type="string"),
     *                     @OA\Property(property="account_chart_category_id", type="string", format="uuid"),
     *                     @OA\Property(property="category", type="object"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function index(Request $request)
    {

        $query = ChartAccount::with(['category']);

        // Filter by authenticated user
        $query->where('user_id', Auth::id());

        // Filter by category title if provided
        if ($request->has('category_name')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('title', 'ILIKE', '%' . $request->category_name . '%');
            });
        }

        $accounts = $query->get();

        return response()->json([
            'message' => 'Chart accounts retrieved successfully',
            'status' => 'success',
            'status_code' => 200,
            'data' => $accounts
        ], 200);
    }
}
