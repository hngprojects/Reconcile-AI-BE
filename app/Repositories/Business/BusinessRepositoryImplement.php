<?php

namespace App\Repositories\Business;

use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\BusinessInfo;

// Ensure the Business model exists in the App\Models namespace
// If it doesn't exist, create it or adjust the namespace accordingly.

class BusinessRepositoryImplement extends Eloquent implements BusinessRepository{

    /**
    * Model class to be used in this repository for the common methods inside Eloquent
    * Don't remove or change $this->model variable name
    * @property Model|mixed $model;
    */
    protected BusinessInfo $model;

    public function __construct(BusinessInfo $model)
    {
        $this->model = $model;
    }

    public function createBusiness(array $data)
    {
        return $this->model->create($data);
    }

    public function updateBusiness(int $id, array $data)
    {
        return $this->model->where('id', $id)->update($data);
    }

    public function getByUserId(int $userId)
    {
        return $this->model->where('user_id', $userId)->first();
    }
}
