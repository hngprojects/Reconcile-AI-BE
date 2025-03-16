<?php

namespace App\Services\NewReconciliation;

use LaravelEasyRepository\BaseService;

interface NewReconciliationService extends BaseService{

    public function usingEmbeddings(string $statement, string $ledger);
    public function usingRecox(string $statement, string $ledger);
}
