<?php

namespace App\Repositories\Reconciliation;

use LaravelEasyRepository\Repository;
use App\Models\User;
use App\Models\Reconciliation;

interface ReconciliationRepository extends Repository
{
    public function store(array $data);
    public function storeResponse(array $data);
    public function get(string $id);
    public function updateRecon(Reconciliation $reconciliation, array $data);
    public function findResponse(Reconciliation $reconciliation);
    public function updateResponse(Reconciliation $reconciliation, array $data);
    public function list(User $user);
}
