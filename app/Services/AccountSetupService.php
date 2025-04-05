<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Repositories\{
    BusinessInfo\BusinessInfoRepository,
    BankAccount\BankAccountRepository,
    BookkeepingLedger\BookkeepingLedgerRepository
};

class AccountSetupService
{
    public function __construct(
        private BusinessInfoRepository $businessInfoRepo,
        private BankAccountRepository $bankAccountRepo,
        private BookkeepingLedgerRepository $ledgerRepo
    ) {}

    public function setupAccount(array $data, int $userId): array
    {
        return DB::transaction(function () use ($data, $userId) {
            // 1. Create Business Info
            $businessInfo = $this->businessInfoRepo->createBusinessInfo([
                'user_id' => $userId,
                'name' => $data['business_name'],
                'type' => $data['business_type'],
                'reporting_year' => $data['reporting_year'],
                'currency' => $data['currency']
            ]);

            // 2. Create Bank Account
            $bankAccount = $this->bankAccountRepo->createBankAccount([
                'user_id' => $userId,
                'bank_name' => $data['bank_name'],
                'account_name' => $data['account_name'],
                'account_number' => $data['account_number'],
                'opening_balance' => $data['opening_balance'] ?? 0,
                'currency' => $data['currency'],
                'is_default' => true
            ]);

            // 3. Create Selected Ledgers
            $ledgerTypes = $data['ledger_types'] ?? ['general']; // Default to general if none selected
            $ledgers = $this->ledgerRepo->createDefaultLedgers($userId, $ledgerTypes);

            return [
                'business_info' => $businessInfo,
                'bank_account' => $bankAccount,
                'ledgers' => $ledgers
            ];
        });
    }
}