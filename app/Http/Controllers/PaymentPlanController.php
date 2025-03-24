<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentPlanController extends Controller
{
    /**
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
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="price", type="number", format="float", example=19.99),
     *                 @OA\Property(property="plan", type="string", example="Premium"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
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
    public function show()
    {
        $user = Auth::user();
        $paymentPlan = $user->paymentPlan;

        return response()->json([
            "status" => true,
            "message" => "Payment plan retrieved successfully.",
            "data" => $paymentPlan,
        ], 200);
    }

    /**
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
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="price", type="number", format="float", example=19.99),
     *                 @OA\Property(property="plan", type="string", example="Premium"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="User already has a payment plan",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="You already have a payment plan."),
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
     *                     property="price",
     *                     type="array",
     *                     @OA\Items(type="string", example="The price field is required.")
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
    public function store(Request $request)
    {
        $request->validate([
            'price' => 'required|numeric',
            'plan' => 'required|string',
        ]);

        $user = Auth::user();

        // Ensure the user doesn't already have a payment plan
        if ($user->paymentPlan) {
            return response()->json([
                "status" => false,
                "message" => "You already have a payment plan.",
                "data" => null,
            ], 400);
        }

        // Create a new payment plan
        $paymentPlan = $user->paymentPlan()->create([
            'price' => $request->price,
            'plan' => $request->plan,
        ]);

        return response()->json([
            "status" => true,
            "message" => "Payment plan created successfully.",
            "data" => $paymentPlan,
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/payment-plan",
     *     summary="Update user's payment plan",
     *     description="Updates the authenticated user's payment plan details",
     *     tags={"Payment Plans"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"price", "plan"},
     *             @OA\Property(property="price", type="number", format="float", example=29.99, description="New price of the payment plan"),
     *             @OA\Property(property="plan", type="string", example="Enterprise", description="New name of the payment plan")
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
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="price", type="number", format="float", example=29.99),
     *                 @OA\Property(property="plan", type="string", example="Enterprise"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No payment plan found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No payment plan found to update."),
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
     *                     property="price",
     *                     type="array",
     *                     @OA\Items(type="string", example="The price field is required.")
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
    public function update(Request $request)
    {
        $request->validate([
            'price' => 'required|numeric',
            'plan' => 'required|string',
        ]);

        $user = Auth::user();

        // Ensure the user has a payment plan to update
        if (!$user->paymentPlan) {
            return response()->json([
                "status" => false,
                "message" => "No payment plan found to update.",
                "data" => null,
            ], 404);
        }

        // Update the payment plan
        $user->paymentPlan->update([
            'price' => $request->price,
            'plan' => $request->plan,
        ]);

        return response()->json([
            "status" => true,
            "message" => "Payment plan updated successfully.",
            "data" => $user->paymentPlan,
        ], 200);
    }
}
