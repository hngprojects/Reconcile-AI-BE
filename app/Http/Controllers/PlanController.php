<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Plan\PlanService;

/**
 * @OA\Tag(name="Plans", description="API Endpoints for managing Plans")
 */

class PlanController extends Controller
{
    protected PlanService $planService;

    public function __construct(PlanService $planService)
    {
        $this->planService = $planService;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/plans",
     *     summary="Create a new plan",
     *     tags={"Plans"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "plan_length", "plan", "amount", "reconciliations_per_month"},
     *             @OA\Property(property="name", type="string", example="Starter Plan"),
     *             @OA\Property(property="description", type="string", example="Basic starter plan"),
     *             @OA\Property(property="plan_length", type="integer", example=30),
     *             @OA\Property(property="plan", type="string", example="starter"),
     *             @OA\Property(property="amount", type="number", format="decimal", example=10.00),
     *             @OA\Property(property="reconciliations_per_month", type="integer", example=10)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Plan created successfully"),
     *     @OA\Response(response=400, description="Invalid input")
     * )
     */

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'plan_length' => 'required|integer',
            'plan' => 'required|string',
            'amount' => 'required|numeric',
            'reconciliations_per_month' => 'required|integer',
        ]);
        $plan = $this->planService->createPlan($data);
        if ($plan) {
            return response()->json(['message' => 'Plan created successfully', 'data' => $plan], 201);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/plans/{id}",
     *     summary="Get plan by ID",
     *     tags={"Plans"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         description="Plan ID"
     *     ),
     *     @OA\Response(response=200, description="Plan retrieved successfully"),
     *     @OA\Response(response=404, description="Plan not found")
     * )
     */

    public function show($id)
    {
        $plan = $this->planService->getById($id);
        if (!$plan) {
            return response()->json(['message' => 'Plan not found'], 404);
        }
        return response()->json($plan);
    }

        /**
     * @OA\Patch(
     *     path="/api/v1/plans/{id}",
     *     summary="Partially update a plan",
     *     description="Updates an existing plan's details. Only the provided fields will be updated.",
     *     tags={"Plans"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the plan to update",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Starter Plan"),
     *             @OA\Property(property="description", type="string", nullable=true, example="Updated description"),
     *             @OA\Property(property="plan_length", type="integer", example=30),
     *             @OA\Property(property="plan", type="string", example="Starter"),
     *             @OA\Property(property="amount", type="number", format="float", example=10.00),
     *             @OA\Property(property="reconciliations_per_month", type="integer", example=10)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Plan updated successfully"),
     *     @OA\Response(response=404, description="Plan not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string',
            'description' => 'sometimes|nullable|string',
            'plan_length' => 'sometimes|required|integer',
            'plan' => 'sometimes|required|string',
            'amount' => 'sometimes|required|numeric',
            'reconciliations_per_month' => 'sometimes|required|integer',
        ]);

        $plan = $this->planService->updatePlan($id, $data);
        if (!$plan) {
            return response()->json(['message' => 'Plan not found'], 404);
        }
        return response()->json(['message' => 'Plan updated successfully', 'data' => $plan]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/plans/{id}",
     *     summary="Delete a plan",
     *     description="Deletes an existing plan",
     *     tags={"Plans"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the plan to delete",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(response=200, description="Plan deleted successfully"),
     *     @OA\Response(response=404, description="Plan not found")
     * )
     */
    public function destroy($id)
    {
        $deleted = $this->planService->deletePlan($id);

        if (!$deleted) {
            return response()->json(['message' => 'Plan not found'], 404);
        }
        return response()->json(['message' => 'Plan deleted successfully']);
    }
}
