<?php

namespace App\Repositories\MatchingTransaction;

use LaravelEasyRepository\Repository;
use App\Models\Ledger;
use App\Models\Statement;
use App\Models\Reconciliation;

interface MatchingTransactionRepository extends Repository{
    public function store(Ledger $ledger, Statement $statement);
    public function remove(Ledger $ledger, Statement $statement);
    public function matchTransactions(Reconciliation $reconciliation);
}
