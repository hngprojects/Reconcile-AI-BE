<?php

namespace App\Services\NewReconciliation;

use LaravelEasyRepository\BaseService;
use App\Models\User;
use App\Models\Reconciliation;

interface NewReconciliationService extends BaseService
{

    public function usingEmbeddings(array $statements, array $ledgers, array $mapper, User $user, Reconciliation $reconciliation);
    public function storeReconciliation($statements, $ledgers, $title, $user);
    public function createReconWithLedgers(array $data, User $user);
    public function matchUnmatch(Reconciliation $reconciliation, array $matches);
    public function fetchResults(Reconciliation $reconciliation);
    public function fetchDetails(Reconciliation $reconciliation);
    public function fetchReconResult(Reconciliation $reconciliation);
    public function export(Reconciliation $reconciliation);
    public function fetchUserReconciliations(User $user);
    public function uploadLedger(array $data);
}
