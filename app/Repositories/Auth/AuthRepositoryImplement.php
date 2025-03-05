<?php

namespace App\Repositories\Auth;

use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\Auth;

class AuthRepositoryImplement extends Eloquent implements AuthRepository{

    /**
    * Model class to be used in this repository for the common methods inside Eloquent
    * Don't remove or change $this->model variable name
    * @property Model|mixed $model;
    */
    protected Auth $model;

    public function __construct(Auth $model)
    {
        $this->model = $model;
    }

    // Write something awesome :)
}
