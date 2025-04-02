<?php

namespace App\Repositories\UserFile;

use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\UserFile;
use App\Models\User;

class UserFileRepositoryImplement extends Eloquent implements UserFileRepository{

    /**
    * Model class to be used in this repository for the common methods inside Eloquent
    * Don't remove or change $this->model variable name
    * @property Model|mixed $model;
    */
    protected UserFile $model;

    public function __construct(UserFile $model)
    {
        $this->model = $model;
    }

    public function store(array $data){
        return $this->model->create($data);
    }

    public function list(User $user){
        return $this->model->where('user_id', '=', $user->id)->get();
    }
}
