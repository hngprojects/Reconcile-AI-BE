<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BankAccountController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/bank-accounts",
     *     summary="Get authenticated user's bank accounts",
     *     description="Retrieves a list of bank accounts for the authenticated user.",
     *     tags={"Bank Accounts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Bank accounts retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Bank Account fetch successful."),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="")
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
     */
    public function index()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'status_code' => 401,
                    'message'    => 'Unauthenticated.'
                ], 401);
            }
    
            $accounts = BankAccount::where('user_id', Auth::id())->get();
            return response()->json([
                'status_code' => 200,
                'message'    => 'Bank Account fetch successful.',
                'data'       => $accounts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message'    => 'Failed to fetch bank accounts.',
                'error'      => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * @OA\Post(
     *     path="/api/v1/bank-accounts",
     *     summary="Create a new bank account",
     *     description="Creates a new bank account for the authenticated user.",
     *     tags={"Bank Accounts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"account_name", "account_number", "bank_name", "opening_balance", "currency"},
     *             @OA\Property(property="account_name", type="string", example="My Savings"),
     *             @OA\Property(property="account_number", type="string", example="123456789"),
     *             @OA\Property(property="bank_name", type="string", example="Bank of Example"),
     *             @OA\Property(property="opening_balance", type="number", example=1000),
     *             @OA\Property(property="currency", type="string", example="USD"),
     *             @OA\Property(property="is_default", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bank account created successfully.",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Bank Account created successfully."),
     *             @OA\Property(property="data", ref="")
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
     */
    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'status_code' => 401,
                    'message'    => 'Unauthenticated.'
                ], 401);
            }
    
            $request->validate([
                'account_name'    => 'required|string|max:255',
                'account_number'  => 'required|string|max:255',
                'bank_name'       => 'required|string|max:255',
                'opening_balance' => 'required|numeric',
                'currency'        => 'required|string|max:3',
                'is_default'      => 'boolean',
            ]);
    
            $account = BankAccount::create([
                'user_id'        => Auth::id(),
                'account_name'   => $request->account_name,
                'account_number' => $request->account_number,
                'bank_name'      => $request->bank_name,
                'opening_balance'=> $request->opening_balance,
                'currency'       => $request->currency,
                'is_default'     => $request->is_default ?? false,
            ]);
    
            return response()->json([
                'status_code' => 200,
                'message'    => 'Bank Account created successfully.',
                'data'       => $account
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message'    => 'Failed to create bank account.',
                'error'      => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * @OA\Put(
     *     path="/api/v1/bank-accounts/{id}/default",
     *     summary="Set bank account as default",
     *     description="Sets a specific bank account as the default for the authenticated user.",
     *     tags={"Bank Accounts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Bank Account ID",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Account set as default successfully.",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Account set as default successfully."),
     *             @OA\Property(property="data", ref="")
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
     */
    public function setDefault(BankAccount $bankAccount)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'status_code' => 401,
                    'message'    => 'Unauthenticated.'
                ], 401);
            }
            
            BankAccount::where('user_id', Auth::id())->update(['is_default' => false]);

            $bankAccount->update(['is_default' => true]);

            return response()->json([
                'status_code' => 200,
                'message'    => 'Account set as default successfully.',
                'data'       => $bankAccount->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message'    => 'Failed to set default bank account.',
                'error'      => $e->getMessage()
            ], 500);
        }
    }
}