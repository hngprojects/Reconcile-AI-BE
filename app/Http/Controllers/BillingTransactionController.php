<?php

namespace App\Http\Controllers;

use App\Models\BillingTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BillingTransactionController extends Controller
{
    //    method to get the user billing transactions
    /**
     * Get user's payment plan history
     *
     * @OA\Get(
     *     path="/api/v1/payment-plan/history",
     *     summary="Get user's payment plan history",
     *     description="Retrieves the authenticated user's payment plan transaction history",
     *     tags={"Payment Plans"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Payment plan history retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="user_id", type="integer", example=123),
     *                     @OA\Property(property="amount", type="number", format="float", example=29.99),
     *                     @OA\Property(property="plan", type="string", example="Enterprise"),
     *                     @OA\Property(property="transaction_date", type="string", format="date-time"),
     *                     @OA\Property(property="status", type="string", example="completed")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="links",
     *                 type="object",
     *                 @OA\Property(property="first", type="string", example="http://example.com/api/v1/payment-plan/history?page=1"),
     *                 @OA\Property(property="last", type="string", example="http://example.com/api/v1/payment-plan/history?page=1"),
     *                 @OA\Property(property="prev", type="string", example=null),
     *                 @OA\Property(property="next", type="string", example=null)
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="from", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=1),
     *                 @OA\Property(property="path", type="string", example="http://example.com/api/v1/payment-plan/history"),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="to", type="integer", example=10),
     *                 @OA\Property(property="total", type="integer", example=10)
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
    public function history()
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'status_code' => 401,
                    'message' => 'Unauthenticated.'
                ], 401);
            }

            $history = BillingTransaction::where('user_id', $user->id)->paginate(10);

            if ($history->isEmpty()) {
                return response()->json([
                    'status_code' => 404,
                    'message' => 'No billing transactions found.',
                    'data' => []
                ], 404);
            }

            return response()->json([
                'status_code' => 200,
                'message' => 'Billing transactions retrieved successfully.',
                'data' => $history
            ]);

        } catch (\Exception $e) {
            Log::error('BillingTransaction Error:', ['error' => $e->getMessage()]);

            return response()->json([
                'status_code' => 500,
                'message' => 'An error occurred while fetching billing transactions.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
