<?php

namespace App\Repositories\Ledger;

use LaravelEasyRepository\Repository;

interface LedgerRepository extends Repository{
    public function store(array $data);
}
