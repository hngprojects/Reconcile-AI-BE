<?php

namespace App\Services\Plan;

use LaravelEasyRepository\ServiceApi;
use App\Repositories\Plan\PlanRepository;

class PlanServiceImplement extends ServiceApi implements PlanService{

    /**
     * set title message api for CRUD
     * @param string $title
     */
     protected string $title = "";
     /**
     * uncomment this to override the default message
     * protected string $create_message = "";
     * protected string $update_message = "";
     * protected string $delete_message = "";
     */

     /**
     * don't change $this->mainRepository variable name
     * because used in extends service class
     */
     protected PlanRepository $mainRepository;

    public function __construct(PlanRepository $mainRepository)
    {
      $this->mainRepository = $mainRepository;
    }

    public function createPlan(array $data)
    {
        return $this->mainRepository->createPlan($data);
    }

    public function updatePlan(string $id, array $data)
    {
        return $this->mainRepository->updatePlan($id, $data);
    }

    public function getById(string $id)
    {
        return $this->mainRepository->getById($id);
    }

    public function getAll()
    {
        return $this->mainRepository->getAll();
    }

    public function deletePlan(string $id)
    {
        return $this->mainRepository->deletePlan($id);
    }
}
