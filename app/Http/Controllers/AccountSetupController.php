<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\SetupAccountRequest;
use App\Services\AccountSetupService;
use Illuminate\Http\JsonResponse;

class AccountSetupController extends Controller
{
    public function __construct(
        private AccountSetupService $accountSetupService
    ) {}

    /**
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
     *         description="Validation error"
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
