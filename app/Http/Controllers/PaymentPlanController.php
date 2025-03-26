<?php

namespace App\Http\Controllers;

use App\Models\BillingTransaction;
use App\Models\Plan;
use App\Models\PaymentPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentPlanController extends Controller
{
    /**
     * Get user's current active payment plan
     * 
     * @OA\Get(
     *     path="/api/v1/payment-plan",
     *     summary="Get user's current payment plan",
     *     description="Retrieves the authenticated user's current payment plan details",
     *     tags={"Payment Plans"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Payment plan retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payment plan retrieved successfully."),
     *             @OA\Property(
     *                 property="data", 
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="plan", type="string", example="Premium"),
     *                 @OA\Property(property="days_remaining", type="integer", example=30),
     *                 @OA\Property(property="is_expired", type="boolean", example=false)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No active payment plan found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No active payment plan found")
     *         )
     *     )
     * )
     */
    public function show()
    {
        // $plan = Auth::user()->activePaymentPlan;
        $user = Auth::user();
        $plan = $user->paymentPlan()->with('plan')->first();
        if (!$plan) {
            return response()->json([
                'status' => false,
                'message' => 'No active payment plan found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'id' => $plan->id,
                'plan' => $plan->plan->plan,
                'days_remaining' => now()->diffInDays($plan->expire_date),
                'is_expired' => $plan->isExpired()
            ]
        ]);
    }

    /**
     * Create a new payment plan
     * 
     * @OA\Post(
     *     path="/api/v1/payment-plan",
     *     summary="Create a new payment plan",
     *     description="Creates a new payment plan for the authenticated user",
     *     tags={"Payment Plans"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"price", "plan"},
     *             @OA\Property(property="price", type="number", format="float", example=19.99, description="Price of the payment plan"),
     *             @OA\Property(property="plan", type="string", example="Premium", description="Name of the payment plan")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Payment plan created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payment plan created successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="plan", type="string", example="Premium"),
     *                 @OA\Property(property="start_date", type="string", format="date-time"),
     *                 @OA\Property(property="expire_date", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Cannot subscribe until current plan is close to expiration."),
     *             @OA\Property(property="data", type="null")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'price'  => 'required|numeric',
            'plan'   => 'required|string',
        ]);

        return DB::transaction(function () use ($request) {
            $user = Auth::user();

            // Find the plan
            $plan = Plan::where('plan', $request->plan)->first();
            if (!$plan) {
                return response()->json([
                    "status" => false,
                    "message" => "Invalid plan name.",
                ], 400);
            }

            // Validate price matches plan amount
            if ($request->price != $plan->amount) {
                return response()->json([
                    "status" => false,
                    "message" => "The price must match the plan amount.",
                    "expected_price" => $plan->amount,
                ], 400);
            }

            // Check existing active plans
            $activePlan = $user->paymentPlan()
                ->where('expire_date', '>', now())
                ->first();

            if ($activePlan) {
                // Check if plan can be renewed (within 5 days of expiration)
                $daysRemaining = now()->diffInDays($activePlan->expire_date);
                if ($daysRemaining > 5) {
                    return response()->json([
                        "status" => false,
                        "message" => "You cannot subscribe until your current plan is close to expiration.",
                    ], 400);
                }
            }

            // Create new plan
            $startDate = now();
            $expireDate = $startDate->copy()->addDays($plan->plan_length);

            $paymentPlan = $user->paymentPlan()->create([
                'plan_id' => $plan->id,
                'start_date' => $startDate,
                'expire_date' => $expireDate,
                'is_active' => true,
                'reconciliations_used' => 0
            ]);
            BillingTransaction::create([
                'user_id' => $user->id,
                'description' => 'Monthly Subscription',
                'status' => 'Successful',
                'plan' => $plan->plan,
                'amount' => $plan->amount
            ]);

            return response()->json([
                "status" => true,
                "message" => "Payment plan created successfully.",
                "data" => $paymentPlan,
            ], 201);
        });
    }

    /**
     * Update user's payment plan
     * 
     * @OA\Put(
     *     path="/api/v1/payment-plan",
     *     summary="Update user's payment plan",
     *     description="Updates the authenticated user's payment plan",
     *     tags={"Payment Plans"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"price", "plan"},
     *             @OA\Property(property="price", type="number", format="float", example=29.99, description="New plan price"),
     *             @OA\Property(property="plan", type="string", example="Enterprise", description="New plan name")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment plan updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payment plan updated successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="plan", type="string", example="Enterprise"),
     *                 @OA\Property(property="start_date", type="string", format="date-time"),
     *                 @OA\Property(property="expire_date", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Cannot update plan until close to expiration."),
     *             @OA\Property(property="data", type="null")
     *         )
     *     )
     * )
     */
    public function update(Request $request)
    {
        $request->validate([
            'price' => 'required|numeric',
            'plan' => 'required|string',
        ]);

        return DB::transaction(function () use ($request) {
            $user = Auth::user();

            // Fetch current active plan
            $currentPlan = $user->paymentPlan()->first();

            if (!$currentPlan || !$currentPlan->is_active) {
                return response()->json([
                    "status" => false,
                    "message" => "No active payment plan found or the plan has not expired yet.",
                ], 400);
            }

            // Fetch new plan and Basic plan
            $newPlan = Plan::where('plan', $request->plan)->first();
            $basicPlan = Plan::where('plan', 'Basic')->first();

            if (!$newPlan) {
                return response()->json([
                    "status" => false,
                    "message" => "Invalid plan name.",
                ], 400);
            }

            // Ensure price matches plan amount
            if ($request->price != $newPlan->amount) {
                return response()->json([
                    "status" => false,
                    "message" => "The price must match the plan amount.",
                    "expected_price" => $newPlan->amount,
                ], 400);
            }

            // Prevent re-subscribing to Basic plan if already on Basic
            if ($basicPlan && $currentPlan->plan_id === $basicPlan->id && $newPlan->id === $basicPlan->id) {
                return response()->json([
                    "status" => false,
                    "message" => "You are already on the Basic plan and cannot subscribe to it again.",
                ], 400);
            }

            // Allow immediate upgrade if current plan is Basic, otherwise ensure expiration
            $startDate = now();
            if (!($basicPlan && $currentPlan->plan_id === $basicPlan->id)) {
                if ($currentPlan->is_active) {
                    return response()->json([
                        "status" => false,
                        "message" => "Cannot update plan until the current one expires.",
                    ], 400);
                }
            }

            // Update payment plan
            $currentPlan->update([
                'plan_id' => $newPlan->id,
                'start_date' => $startDate,
                'expire_date' => $startDate->copy()->addDays($newPlan->plan_length),
                'reconciliations_used' => 0,
                'is_active' => true, // Reactivating the plan after renewal
            ]);

            // Record billing transaction
            BillingTransaction::create([
                'user_id' => $user->id,
                'description' => 'Monthly Subscription',
                'status' => 'Successful',
                'plan' => $newPlan->plan,
                'amount' => $newPlan->amount
            ]);

            return response()->json([
                "status" => true,
                "message" => "Payment plan updated successfully.",
                "data" => $currentPlan,
            ], 200);
        });
    }
}
