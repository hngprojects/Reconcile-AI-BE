<?php

namespace App\Repositories\Statement;

use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\Statement;
use Illuminate\Support\Carbon;

class StatementRepositoryImplement extends Eloquent implements StatementRepository{

    /**
    * Model class to be used in this repository for the common methods inside Eloquent
    * Don't remove or change $this->model variable name
    * @property Model|mixed $model;
    */
    protected Statement $model;

    public function __construct(Statement $model)
    {
        $this->model = $model;
    }

    public function store(array $data){
        $data['date'] = Carbon::parse($data['date'])->format('Y-m-d');

        return $this->model->firstOrCreate([
            'reconciliation_id' => $data['reconciliation_id'],
            'description' => $data['description'],
            'amount' => $data['amount'],
            'date' => $data['date']
        ]);
    }
}
