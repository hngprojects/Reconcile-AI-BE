<?php

namespace App\Repositories\Ledger;

use LaravelEasyRepository\Repository;
use App\Models\Reconciliation;

interface LedgerRepository extends Repository{
    public function store(array $data);
    public function storeMany(array $ledgers, Reconciliation $reconciliation);
    public function findAll(Reconciliation $reconciliation);
}
