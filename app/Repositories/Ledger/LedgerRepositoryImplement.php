<?php

namespace App\Repositories\Ledger;

use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\Ledger;

class LedgerRepositoryImplement extends Eloquent implements LedgerRepository{

    /**
    * Model class to be used in this repository for the common methods inside Eloquent
    * Don't remove or change $this->model variable name
    * @property Model|mixed $model;
    */
    protected Ledger $model;

    public function __construct(Ledger $model)
    {
        $this->model = $model;
    }

    public function store(array $data){
        return $this->model->firstOrCreate([
            'reconciliation_id' => $data['reconciliation_id'],
            'description' => $data['Description'],
            'amount' => $data['Amount'],
            'date' => $data['Date']
        ]);
    }
}
