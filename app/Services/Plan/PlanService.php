<?php

namespace App\Services\Plan;

use LaravelEasyRepository\BaseService;

interface PlanService extends BaseService{
    public function createPlan(array $data);
    public function updatePlan(string $id, array $data);
    public function getById(string $id);
    public function getAll();
    public function deletePlan(string $id);
}
