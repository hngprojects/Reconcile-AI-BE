<?php

namespace App\Repositories\MatchingTransaction;

use LaravelEasyRepository\Repository;
use App\Models\Ledger;
use App\Models\Statement;

interface MatchingTransactionRepository extends Repository{
    public function store(Ledger $ledger, Statement $statement, int $score);
    public function remove(Ledger $ledger, Statement $statement);
}
