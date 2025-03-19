<?php

namespace App\Services\NewReconciliation;

use LaravelEasyRepository\BaseService;
use App\Models\User;

interface NewReconciliationService extends BaseService{

    public function usingEmbeddings(array $statements, array $ledgers, User $user);
}
