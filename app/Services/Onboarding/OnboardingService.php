<?php

namespace App\Services\Onboarding;

use LaravelEasyRepository\BaseService;

interface OnboardingService extends BaseService{

    public function saveBusinessDetails(array $data);
    public function saveBankAccount(array $data);
    public function setupLedger(array $data);
    public function completeOnboarding(int $userId);
    public function getOnboardingStatus(int $userId);
    public function getBusiness(int $userId);
    public function getBankAccounts(string $businessId);
    public function getLedgers(string $businessId);
}
