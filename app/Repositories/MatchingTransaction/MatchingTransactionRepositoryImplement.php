<?php

namespace App\Repositories\MatchingTransaction;

use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\MatchingTransaction;
use App\Models\Ledger;
use App\Models\Statement;

class MatchingTransactionRepositoryImplement extends Eloquent implements MatchingTransactionRepository{

    /**
    * Model class to be used in this repository for the common methods inside Eloquent
    * Don't remove or change $this->model variable name
    * @property Model|mixed $model;
    */
    protected MatchingTransaction $model;

    public function __construct(MatchingTransaction $model)
    {
        $this->model = $model;
    }

    public function store(Ledger $ledger, Statement $statement, int $score){
        return $this->model->create([
            'ledger_id' => $ledger->id,
            'statement_id' => $statement->id,
            'score' => $score
        ]);
    }

    public function remove(Ledger $ledger, Statement $statement){
        return $this->model->where([
            'ledger_id' => $ledger->id,
            'statement_id' => $statement->id,
        ])->first()->delete();
    }
}
