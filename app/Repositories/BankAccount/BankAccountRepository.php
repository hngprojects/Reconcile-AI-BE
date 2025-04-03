<?php

namespace App\Repositories\BankAccount;

use LaravelEasyRepository\Repository;

interface BankAccountRepository extends Repository{

    public function createAccount(array $data);
    public function setPrimaryAccount(string $businessId, string $accountId);
    public function getByBusiness(string $businessId);
}
