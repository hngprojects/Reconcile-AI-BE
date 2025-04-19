<?php

namespace App\Repositories\LedgerPayment;

use LaravelEasyRepository\Repository;
use App\Models\Ledger;

interface LedgerPaymentRepository extends Repository{
    public function findByLedger(Ledger $ledger);
}
