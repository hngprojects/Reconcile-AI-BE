<?php

namespace App\Repositories\BankAccount;

use LaravelEasyRepository\Repository;
use App\Models\BankAccount;

interface BankAccountRepository extends Repository{

    public function createBankAccount(array $data): BankAccount;
}
