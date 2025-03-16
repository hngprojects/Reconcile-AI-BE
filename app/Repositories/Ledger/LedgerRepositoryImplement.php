<?php

namespace App\Repositories\Ledger;

use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\Ledger;
use App\Models\Reconciliation;
use Illuminate\Support\Facades\DB;

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
            'person' => $data['Person'],
            'amount' => $data['Amount'],
            'other_information' => $data['Other Information'],
            'date' => $data['Date']
        ]);
    }


    public function storeMany(array $ledgers, Reconciliation $reconciliation){
        foreach ($ledgers as $ledger) {
            $this->model->firstOrCreate([
                'reconciliation_id' => $reconciliation->id,
                'person' => $ledger['Person'],
                'amount' => (string) $ledger['Amount'],
                'other_information' => $ledger['Other Information'],
                'date' => $ledger['Date']
            ]);
        }
        return;
    }

    public function findAll(Reconciliation $reconciliation){
        return Ledger::where('reconciliation_id', '=', $reconciliation->id)->get();
    }
}
