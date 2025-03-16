<?php

namespace App\Repositories\Statement;

use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\Statement;
use App\Models\Reconciliation;

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
        return $this->model->firstOrCreate([
            'reconciliation_id' => $data['reconciliation_id'],
            'person' => $data['Person'],
            'amount' => $data['Amount'],
            'other_information' => $data['Other Information'],
            'date' => $data['Date']
        ]);
    }

    public function storeMany(array $statements, Reconciliation $reconciliation){
        foreach ($statements as $statement) {
            $this->model->firstOrCreate([
                'reconciliation_id' => $reconciliation->id,
                'person' => $statement['Person'],
                'amount' => (string) $statement['Amount'],
                'other_information' => $ledger['Other Information'],
                'date' => $statement['Date']
            ]);
        }
        return;
    }

    public function findAll(Reconciliation $reconciliation){
        return Statement::where('reconciliation_id', '=', $reconciliation->id)->get();
    }

    public function addVector(Statement $statement, array $data){
        $statement->vector = json_encode($data);

        return $ledger;
    }
}
