<?php

namespace App\Repositories\Reconciliation;

use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\Reconciliation;
use App\Models\User;
use App\Models\ReconciledRecord;
use Illuminate\Support\Str;

class ReconciliationRepositoryImplement extends Eloquent implements ReconciliationRepository{

    /**
    * Model class to be used in this repository for the common methods inside Eloquent
    * Don't remove or change $this->model variable name
    * @property Model|mixed $model;
    */
    protected Reconciliation $model;

    public function __construct(Reconciliation $model, ReconciledRecord $record)
    {
        $this->model = $model;
        $this->recordModel = $record;
    }

    public function store(array $data){
        $reconciliation = new Reconciliation();
        $reconciliation->id = Str::uuid();
        $reconciliation->user_id = $data['user_id'];
        $reconciliation->option = $data['option'];
        $reconciliation->save();

        return $reconciliation;
    }

    public function list(User $user){
        return $this->model->where('user_id', '=', $user->id)->get();
    }

    public function storeResponse(array $data)
    {
        return $this->recordModel->create($data);
    }
}
