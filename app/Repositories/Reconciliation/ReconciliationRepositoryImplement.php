<?php

namespace App\Repositories\Reconciliation;

use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\Reconciliation;
use App\Models\User;

class ReconciliationRepositoryImplement extends Eloquent implements ReconciliationRepository{

    /**
    * Model class to be used in this repository for the common methods inside Eloquent
    * Don't remove or change $this->model variable name
    * @property Model|mixed $model;
    */
    protected Reconciliation $model;

    public function __construct(Reconciliation $model)
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
