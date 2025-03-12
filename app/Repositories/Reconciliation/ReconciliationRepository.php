<?php

namespace App\Repositories\Reconciliation;

use LaravelEasyRepository\Repository;
use App\Models\User;

interface ReconciliationRepository extends Repository{
    public function store(array $data);
    public function storeResponse(array $data);
    public function list(User $user);
}
