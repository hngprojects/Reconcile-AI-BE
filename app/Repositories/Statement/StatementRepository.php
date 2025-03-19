<?php

namespace App\Repositories\Statement;

use App\Models\Reconciliation;
use App\Models\Statement;

interface StatementRepository{

    public function store(array $data);
    public function storeMany(array $statements, Reconciliation $reconciliation);
    public function findAll(Reconciliation $reconciliation);
    public function findById(string $id);
    public function addVector(Statement $statement, array $data);
}
