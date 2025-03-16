<?php

namespace App\Repositories\Statement;

use LaravelEasyRepository\Repository;
use App\Models\Reconciliation;

interface StatementRepository extends Repository{

    public function store(array $data);
    public function storeMany(array $statements, Reconciliation $reconciliation);
    public function findAll(Reconciliation $reconciliation);
}
