<?php

namespace App\Services\Onboarding;

use LaravelEasyRepository\ServiceApi;
use App\Repositories\Onboarding\OnboardingRepository;
use App\Repositories\Business\BusinessRepository;
use App\Repositories\BankAccount\BankAccountRepository;
use App\Repositories\CompanyLedger\CompanyLedgerRepository;
use App\Repositories\OnboardingProgress\OnboardingProgressRepository;

class OnboardingServiceImplement extends ServiceApi implements OnboardingService{
    /**
     * set title message api for CRUD
     * @param string $title
     */
     protected string $title = "Onboarding";
     /**
     * uncomment this to override the default message
     * protected string $create_message = "";
     * protected string $update_message = "";
     * protected string $delete_message = "";
     */

     /**
     * don't change $this->mainRepository variable name
     * because used in extends service class
     */
    //  protected OnboardingRepository $mainRepository;

    public function __construct(
      protected BusinessRepository $businessRepo,
      protected BankAccountRepository $bankAccountRepo,
      protected CompanyLedgerRepository $ledgerRepo,
      protected OnboardingProgressRepository $progressRepo
    ) {}

    public function saveBusinessDetails(array $data)
    {
        $business = $this->businessRepo->createBusiness($data);
        $this->progressRepo->completeStep($data['user_id'], 'basics');
        return $business;
    }

    public function saveBankAccount(array $data)
    {
        $account = $this->bankAccountRepo->createAccount($data);
        if ($data['is_primary'] ?? false) {
            $this->bankAccountRepo->setPrimaryAccount($data['business_infos_id'], $account->id);
        }
        $this->progressRepo->completeStep($data['user_id'], 'bank');
        return $account;
    }

    public function setupLedger(array $data)
    {
        // Create a copy of the data without user_id
        $ledgerData = $data;
        unset($ledgerData['user_id']);
        $ledger = $this->ledgerRepo->createLedger($ledgerData);
        if ($data['is_active'] ?? false) {
            $this->ledgerRepo->activateLedger($data['business_infos_id'], $ledger->id);
        }
        $this->progressRepo->completeStep($data['user_id'], 'ledger');
        return $ledger;
    }

    public function completeOnboarding(int $userId)
    {
        $this->progressRepo->completeStep($userId, 'finish');
        return $this->progressRepo->getProgress($userId);
    }

    public function getOnboardingStatus(int $userId)
    {
        return $this->progressRepo->getProgress($userId);
    }

    public function getBusiness(int $userId)
    {
        return $this->businessRepo->getByUserId($userId);
    }

    public function getBankAccounts(string $businessId)
    {
        return $this->bankAccountRepo->getByBusiness($businessId);
    }

    public function getLedgers(string $businessId)
    {
        return $this->ledgerRepo->getByBusiness($businessId);
    }
}
