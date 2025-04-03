<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Onboarding\OnboardingService;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Onboarding",
 *     description="API Endpoints for User Onboarding Process"
 * )
 */
class OnboardingController extends Controller
{
    protected $onboardingService;

    public function __construct(OnboardingService $onboardingService)
    {
        $this->onboardingService = $onboardingService;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/onboarding/business",
     *     tags={"Onboarding"},
     *     summary="Save business details",
     *     description="Save the basic business information during onboarding",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","type","reporting_year","currency"},
     *             @OA\Property(property="name", type="string", example="My Business"),
     *             @OA\Property(property="type", type="string", example="llc"),
     *             @OA\Property(property="reporting_year", type="string", example="January-December"),
     *             @OA\Property(property="currency", type="string", example="NGN")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Business details saved successfully",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     )
     * )
     */
    public function saveBusiness(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'type' => 'required|string',
            'reporting_year' => 'required|string',
            'currency' => 'required|string|size:3'
        ]);

        $data['user_id'] = Auth::id();

        $business = $this->onboardingService->saveBusinessDetails($data);

        return $this->successResponse($business, 'Business details saved successfully');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/onboarding/bank",
     *     tags={"Onboarding"},
     *     summary="Save bank account details",
     *     description="Save the bank account information during onboarding",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"business_infos_id","bank_name","account_name","account_number","opening_balance"},
     *             @OA\Property(property="business_infos_id", type="integer", example=1),
     *             @OA\Property(property="bank_name", type="string", example="Guaranty Trust Bank"),
     *             @OA\Property(property="account_name", type="string", example="Business Account"),
     *             @OA\Property(property="account_number", type="string", example="1234567890"),
     *             @OA\Property(property="opening_balance", type="number", format="float", example=1000.00),
     *             @OA\Property(property="is_primary", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bank account saved successfully",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     )
     * )
     */
    public function saveBank(Request $request)
    {
        $data = $request->validate([
            'business_infos_id' => 'required|exists:business_infos,id',
            'bank_name' => 'required|string',
            'account_name' => 'required|string',
            'account_number' => 'required|string',
            'opening_balance' => 'required|numeric',
            'is_primary' => 'sometimes|boolean'
        ]);

        $data['user_id'] = Auth::id();

        $account = $this->onboardingService->saveBankAccount($data);

        return $this->successResponse($account, 'Bank account saved successfully');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/onboarding/ledger",
     *     tags={"Onboarding"},
     *     summary="Setup company ledger",
     *     description="Setup the company ledger during onboarding",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"business_infos_id","type"},
     *             @OA\Property(property="business_infos_id", type="integer", example=1),
     *             @OA\Property(property="type", type="string", example="general"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ledger setup successfully",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     )
     * )
     */
    public function saveLedger(Request $request)
    {
        $data = $request->validate([
            'business_infos_id' => 'required|exists:business_infos,id',
            'type' => 'required|in:general,vendor,customer',
            'is_active' => 'sometimes|boolean'
        ]);

        $data['user_id'] = Auth::id();

        $ledger = $this->onboardingService->setupLedger($data);

        return $this->successResponse($ledger, 'Ledger setup successfully');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/onboarding/complete",
     *     tags={"Onboarding"},
     *     summary="Complete onboarding",
     *     description="Mark the onboarding process as complete",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Onboarding completed successfully",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     )
     * )
     */
    public function complete(Request $request)
    {
        $progress = $this->onboardingService->completeOnboarding(Auth::id());

        return $this->successResponse($progress, 'Onboarding completed successfully');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/onboarding/status",
     *     tags={"Onboarding"},
     *     summary="Get onboarding status",
     *     description="Get the current status of the onboarding process",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Onboarding status retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Onboarding status retrieved"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="progress", type="object"),
     *                 @OA\Property(property="business", type="object"),
     *                 @OA\Property(property="bank_accounts", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="ledgers", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     )
     * )
     */
    public function status()
    {
        $status = $this->onboardingService->getOnboardingStatus(Auth::id());
        $business = $this->onboardingService->getBusiness(Auth::id());
        $accounts = $business ? $this->onboardingService->getBankAccounts($business->id) : [];
        $ledgers = $business ? $this->onboardingService->getLedgers($business->id) : [];

        return $this->successResponse([
            'progress' => $status,
            'business' => $business,
            'bank_accounts' => $accounts,
            'ledgers' => $ledgers
        ], 'Onboarding status retrieved');
    }
}
