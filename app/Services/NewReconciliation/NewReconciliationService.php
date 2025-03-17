<?php

namespace App\Services\NewReconciliation;

use LaravelEasyRepository\BaseService;
use App\Models\User;

interface NewReconciliationService extends BaseService{

    public function usingEmbeddings(string $statement, string $ledger, User $user);
    public function usingRecox(string $statement, string $ledger, User $user);
}
