<?php

namespace App\Repositories\BusinessInfo;

use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\BusinessInfo;

class BusinessInfoRepositoryImplement extends Eloquent implements BusinessInfoRepository{

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

    public function createBusinessInfo(array $data): BusinessInfo
    {
        return $this->model->create($data);
    }
}
