<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\SetupAccountRequest;
use App\Services\AccountSetupService;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Account",
 *     description="Account setup operations"
 * )
 */
class AccountSetupController extends Controller
{
    /**
     * @OA\Schema(
     *     schema="AccountSetupResponse",
     *     type="object",
     *     @OA\Property(property="success", type="boolean", example=true),
     *     @OA\Property(
     *         property="data",
     *         type="object",
     *         @OA\Property(property="business_name", type="string", example="Dayo & Co."),
     *         @OA\Property(property="business_type", type="string", example="Retail"),
     *         @OA\Property(property="currency", type="string", example="NGN"),
     *         @OA\Property(property="reporting_year", type="string", example="January - December"),
     *         @OA\Property(property="bank_name", type="string", example="Access Bank"),
     *         @OA\Property(property="account_name", type="string", example="Dayo Business"),
     *         @OA\Property(property="account_number", type="string", example="1234567890"),
     *         @OA\Property(property="opening_balance", type="number", format="float", example=10000),
     *         @OA\Property(
     *             property="ledger_types",
     *             type="array",
     *             @OA\Items(type="string", enum={"general", "vendor", "customer"})
     *         )
     *     ),
     *     @OA\Property(property="message", type="string", example="Account setup completed successfully")
     * )
     */
    public function __construct(
        private AccountSetupService $accountSetupService
    ) {}

    /**
     * Setup new account
     *
     * @OA\Post(
     *     path="/api/account/setup",
     *     summary="Complete account setup",
     *     tags={"Account"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/AccountSetupRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Account created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/AccountSetupResponse")
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
     *                     @OA\Items(type="string", example="The account number has already been taken.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Account setup failed"),
     *             @OA\Property(property="error", type="string", example="Error message")
     *         )
     *     )
     * )
     */
    public function __invoke(SetupAccountRequest $request): JsonResponse
    {
        try {
            $result = $this->accountSetupService->setupAccount(
                $request->validated(),
                $request->user()->id
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Account setup completed successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Account setup failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
