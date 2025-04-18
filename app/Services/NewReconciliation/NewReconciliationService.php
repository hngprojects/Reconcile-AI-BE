<?php

namespace App\Services\NewReconciliation;

use LaravelEasyRepository\BaseService;
use App\Models\User;
use App\Models\Reconciliation;

interface NewReconciliationService extends BaseService{

    public function usingEmbeddings(array $statements, array $ledgers, User $user, Reconciliation $reconciliation);
    public function storeReconciliation($statements, $ledgers, $title, $user);
    public function matchUnmatch(Reconciliation $reconciliation, array $statements, array $ledgers, string $action);
    public function fetchResults(Reconciliation $reconciliation);
    public function fetchUserReconciliations(User $user);
    public function uploadLedger(array $data);
}
