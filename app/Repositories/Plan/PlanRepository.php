<?php

namespace App\Repositories\Plan;

use LaravelEasyRepository\Repository;

interface PlanRepository extends Repository{

    public function createPlan(array $data);
    public function updatePlan(string $id, array $data);
    public function getById(string $id);
    public function getAll();
    public function deletePlan(string $id);
}
