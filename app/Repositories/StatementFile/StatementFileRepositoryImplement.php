<?php

namespace App\Repositories\StatementFile;

use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\StatementFile;

class StatementFileRepositoryImplement extends Eloquent implements StatementFileRepository{

    /**
    * Model class to be used in this repository for the common methods inside Eloquent
    * Don't remove or change $this->model variable name
    * @property Model|mixed $model;
    */
    protected StatementFile $model;

    public function __construct(StatementFile $model)
    {
        $this->model = $model;
    }

    public function store(array $data){
        return $this->model->create($data);
    }
}
