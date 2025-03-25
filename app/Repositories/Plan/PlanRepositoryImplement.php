<?php

namespace App\Repositories\Plan;

use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\Plan;

class PlanRepositoryImplement extends Eloquent implements PlanRepository{

    /**
    * Model class to be used in this repository for the common methods inside Eloquent
    * Don't remove or change $this->model variable name
    * @property Model|mixed $model;
    */
    protected Plan $model;

    public function __construct(Plan $model)
    {
        $this->model = $model;
    }

    public function createPlan(array $data)
    {
        return $this->model->create($data);
    }

    public function updatePlan(string $id, array $data)
    {
        $plan = $this->model->findOrFail($id);
        $plan->update($data);
        return $plan;
    }

    public function getById(string $id)
    {
        return $this->model->findOrFail($id);
    }

    public function getAll()
    {
        return $this->model->all();
    }

    public function deletePlan(string $id)
    {
        $plan = $this->model->findOrFail($id);
        return $plan->delete();
    }
}
