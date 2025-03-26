<?php

namespace App\Repositories\Auth;

use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\User;

class AuthRepositoryImplement extends Eloquent implements AuthRepository{

    /**
    * Model class to be used in this repository for the common methods inside Eloquent
    * Don't remove or change $this->model variable name
    * @property Model|mixed $model;
    */
    protected User $model;

    public function __construct(User $model)
    {
        $this->model = $model;
    }
    
    public function register($data)
    {
        return $this->model->create(
            [
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => bcrypt($data['password']),
            ]
        );
    }
}
