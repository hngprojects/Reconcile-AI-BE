<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BankAccountController extends Controller
{
    /**
     * Create a new bank account
     *
     * @OA\Post(
     *     path="/api/v1/bank-accounts",
     *     summary="Create a new bank account",
     *     description="Creates a new bank account for the authenticated user",
     *     tags={"Bank Accounts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"bank_name", "account_number", "account_name", "opening_balance", "currency"},
     *             @OA\Property(property="bank_name", type="string", example="Chase Bank", description="Name of the bank"),
     *             @OA\Property(property="account_number", type="string", example="1234567890", description="Account number"),
     *             @OA\Property(property="account_name", type="string", example="John Doe", description="Name on the account"),
     *             @OA\Property(property="opening_balance", type="number", format="float", example=1000.00, description="Initial balance"),
     *             @OA\Property(property="currency", type="string", example="USD", description="Three-letter currency code"),
     *             @OA\Property(property="is_default", type="boolean", example=true, description="Set as default account")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Bank account created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Bank account created successfully"),
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="status_code", type="integer", example=201),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="bank_name", type="string", example="Chase Bank"),
     *                 @OA\Property(property="account_number", type="string", example="1234567890"),
     *                 @OA\Property(property="account_name", type="string", example="John Doe"),
     *                 @OA\Property(property="opening_balance", type="number", format="float", example=1000.00),
     *                 @OA\Property(property="currency", type="string", example="USD"),
     *                 @OA\Property(property="is_default", type="boolean", example=true),
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
     *                     property="bank_name",
     *                     type="array",
     *                     @OA\Items(type="string", example="The bank name field is required.")
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
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:50',
            'account_name' => 'required|string|max:255',
            'opening_balance' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'is_default' => 'sometimes|boolean',
        ]);

        // Add user_id to the validated data
        $validated['user_id'] = Auth::id();

        // If this account is being set as default, unset all other defaults
        if (isset($validated['is_default']) && $validated['is_default']) {
            $this->unsetDefaultAccounts();
        }

        // If this is the first account for the user, make it default
        $accountCount = BankAccount::where('user_id', Auth::id())->count();
        if ($accountCount === 0) {
            $validated['is_default'] = true;
        }

        $bankAccount = BankAccount::create($validated);

        return response()->json([
            'message' => 'Bank account created successfully',
            'status' => 'success',
            'status_code' => 201,
            'data' => $bankAccount
        ], 201);
    }
    /**
     * Update an existing bank account
     *
     * @OA\Put(
     *     path="/api/v1/bank-accounts/{bankAccount}",
     *     summary="Update a bank account",
     *     description="Updates an existing bank account for the authenticated user",
     *     tags={"Bank Accounts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="bankAccount",
     *         in="path",
     *         required=true,
     *         description="ID of the bank account to update",
     *          @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="bank_name", type="string", example="Bank of America", description="Name of the bank"),
     *             @OA\Property(property="account_number", type="string", example="9876543210", description="Account number"),
     *             @OA\Property(property="account_name", type="string", example="John Doe", description="Name on the account"),
     *             @OA\Property(property="opening_balance", type="number", format="float", example=2000.00, description="Initial balance"),
     *             @OA\Property(property="currency", type="string", example="USD", description="Three-letter currency code"),
     *             @OA\Property(property="is_default", type="boolean", example=true, description="Set as default account")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bank account updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Bank account updated successfully"),
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="bank_name", type="string", example="Bank of America"),
     *                 @OA\Property(property="account_number", type="string", example="9876543210"),
     *                 @OA\Property(property="account_name", type="string", example="John Doe"),
     *                 @OA\Property(property="opening_balance", type="number", format="float", example=2000.00),
     *                 @OA\Property(property="currency", type="string", example="USD"),
     *                 @OA\Property(property="is_default", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T00:00:00.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-01T00:00:00.000000Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized access",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized access"),
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="status_code", type="integer", example=403),
     *             @OA\Property(property="data", type="null", example=null)
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
     *                     property="bank_name",
     *                     type="array",
     *                     @OA\Items(type="string", example="The bank name must be a string.")
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
     * @param BankAccount $bankAccount
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, BankAccount $bankAccount)
    {
        // Check if user owns this account
        if (Auth::id() !== $bankAccount->user_id) {
            return response()->json([
                'message' => 'Unauthorized access',
                'status' => 'error',
                'status_code' => 403,
                'data' => null
            ], 403);
        }

        $validated = $request->validate([
            'bank_name' => 'sometimes|string|max:255',
            'account_number' => 'sometimes|string|max:50',
            'account_name' => 'sometimes|string|max:255',
            'opening_balance' => 'sometimes|numeric|min:0',
            'currency' => 'sometimes|string|size:3',
            'is_default' => 'sometimes|boolean',
        ]);

        // If this account is being set as default, unset all other defaults
        if (isset($validated['is_default']) && $validated['is_default']) {
            $this->unsetDefaultAccounts();
        }

        $bankAccount->update($validated);

        return response()->json([
            'message' => 'Bank account updated successfully',
            'status' => 'success',
            'status_code' => 200,
            'data' => $bankAccount
        ], 200);
    }

    /**
     * Delete a bank account
     *
     * @OA\Delete(
     *     path="/api/v1/bank-accounts/{bankAccount}",
     *     summary="Delete a bank account",
     *     description="Deletes a bank account for the authenticated user",
     *     tags={"Bank Accounts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="bankAccount",
     *         in="path",
     *         required=true,
     *         description="ID of the bank account to delete",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bank account deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Bank account deleted successfully"),
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="data", type="null", example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized access",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized access"),
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="status_code", type="integer", example=403),
     *             @OA\Property(property="data", type="null", example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Cannot delete the only bank account",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Cannot delete the only bank account"),
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="status_code", type="integer", example=422),
     *             @OA\Property(property="data", type="null", example=null)
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
     * @param BankAccount $bankAccount
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(BankAccount $bankAccount)
    {
        // Check if user owns this account
        if (Auth::id() !== $bankAccount->user_id) {
            return response()->json([
                'message' => 'Unauthorized access',
                'status' => 'error',
                'status_code' => 403,
                'data' => null
            ], 403);
        }

        // Check if this is the last account
        $accountCount = BankAccount::where('user_id', Auth::id())->count();

        if ($accountCount <= 1) {
            return response()->json([
                'message' => 'Cannot delete the only bank account',
                'status' => 'error',
                'status_code' => 422,
                'data' => null
            ], 422);
        }

        // If deleting the default account, set another one as default
        if ($bankAccount->is_default) {
            $newDefault = BankAccount::where('user_id', Auth::id())
                ->where('id', '!=', $bankAccount->id)
                ->first();

            if ($newDefault) {
                $newDefault->update(['is_default' => true]);
            }
        }

        $bankAccount->delete();

        return response()->json([
            'message' => 'Bank account deleted successfully',
            'status' => 'success',
            'status_code' => 200,
            'data' => null
        ], 200);
    }
    /**
     * Toggle the default status of a bank account
     *
     * @OA\Patch(
     *     path="/api/v1/bank-accounts/{bankAccount}/toggle-default",
     *     summary="Set a bank account as default",
     *     description="Sets a bank account as the default account for the authenticated user",
     *     tags={"Bank Accounts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="bankAccount",
     *         in="path",
     *         required=true,
     *         description="ID of the bank account to set as default",
     *          @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bank account set as default successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Bank account set as default successfully"),
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="bank_name", type="string", example="Chase Bank"),
     *                 @OA\Property(property="account_number", type="string", example="1234567890"),
     *                 @OA\Property(property="account_name", type="string", example="John Doe"),
     *                 @OA\Property(property="opening_balance", type="number", format="float", example=1000.00),
     *                 @OA\Property(property="currency", type="string", example="USD"),
     *                 @OA\Property(property="is_default", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T00:00:00.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-01T00:00:00.000000Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized access",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized access"),
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="status_code", type="integer", example=403),
     *             @OA\Property(property="data", type="null", example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Account already set as default",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="This account is already set as default"),
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="status_code", type="integer", example=422),
     *             @OA\Property(property="data", type="null", example=null)
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
     * @param BankAccount $bankAccount
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleDefault(BankAccount $bankAccount)
    {
        // Check if user owns this account
        if (Auth::id() !== $bankAccount->user_id) {
            return response()->json([
                'message' => 'Unauthorized access',
                'status' => 'error',
                'status_code' => 403,
                'data' => null
            ], 403);
        }

        // If account is already default, we can't unset it without setting another
        if ($bankAccount->is_default) {
            return response()->json([
                'message' => 'This account is already set as default',
                'status' => 'error',
                'status_code' => 422,
                'data' => null
            ], 422);
        }

        // Unset all defaults and set this one as default
        $this->unsetDefaultAccounts();
        $bankAccount->update(['is_default' => true]);

        return response()->json([
            'message' => 'Bank account set as default successfully',
            'status' => 'success',
            'status_code' => 200,
            'data' => $bankAccount
        ], 200);
    }

    /**
     * Get all bank accounts for the authenticated user
     *
     * @OA\Get(
     *     path="/api/v1/bank-accounts",
     *     summary="List all bank accounts",
     *     description="Retrieves all bank accounts for the authenticated user",
     *     tags={"Bank Accounts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Bank accounts retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Bank accounts retrieved successfully"),
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="user_id", type="integer", example=1),
     *                     @OA\Property(property="bank_name", type="string", example="Chase Bank"),
     *                     @OA\Property(property="account_number", type="string", example="1234567890"),
     *                     @OA\Property(property="account_name", type="string", example="John Doe"),
     *                     @OA\Property(property="opening_balance", type="number", format="float", example=1000.00),
     *                     @OA\Property(property="currency", type="string", example="USD"),
     *                     @OA\Property(property="is_default", type="boolean", example=true),
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
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $accounts = BankAccount::where('user_id', Auth::id())->get();

        return response()->json([
            'message' => 'Bank accounts retrieved successfully',
            'status' => 'success',
            'status_code' => 200,
            'data' => $accounts
        ], 200);
    }

    /**
     * Get a specific bank account
     *
     * @OA\Get(
     *     path="/api/v1/bank-accounts/{bankAccount}",
     *     summary="Get bank account details",
     *     description="Retrieves details of a specific bank account for the authenticated user",
     *     tags={"Bank Accounts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="bankAccount",
     *         in="path",
     *         required=true,
     *         description="ID of the bank account to retrieve",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bank account retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Bank account retrieved successfully"),
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="bank_name", type="string", example="Chase Bank"),
     *                 @OA\Property(property="account_number", type="string", example="1234567890"),
     *                 @OA\Property(property="account_name", type="string", example="John Doe"),
     *                 @OA\Property(property="opening_balance", type="number", format="float", example=1000.00),
     *                 @OA\Property(property="currency", type="string", example="USD"),
     *                 @OA\Property(property="is_default", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T00:00:00.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-01T00:00:00.000000Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized access",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized access"),
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="status_code", type="integer", example=403),
     *             @OA\Property(property="data", type="null", example=null)
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
     * @param BankAccount $bankAccount
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(BankAccount $bankAccount)
    {
        // Check if user owns this account
        if (Auth::id() !== $bankAccount->user_id) {
            return response()->json([
                'message' => 'Unauthorized access',
                'status' => 'error',
                'status_code' => 403,
                'data' => null
            ], 403);
        }

        return response()->json([
            'message' => 'Bank account retrieved successfully',
            'status' => 'success',
            'status_code' => 200,
            'data' => $bankAccount
        ], 200);
    }

    /**
     * Helper method to unset default status from all accounts
     */
    private function unsetDefaultAccounts()
    {
        BankAccount::where('user_id', Auth::id())
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }
}
