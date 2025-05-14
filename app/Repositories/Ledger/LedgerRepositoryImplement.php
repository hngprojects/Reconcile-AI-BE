<?php

namespace App\Repositories\Ledger;

use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\Ledger;
use App\Models\Reconciliation;
use Illuminate\Support\Facades\DB;
use Pgvector\Laravel\Vector;
use Illuminate\Support\Facades\Log;

class LedgerRepositoryImplement extends Eloquent implements LedgerRepository
{

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

    public function store(array $data)
    {
        return $this->model->firstOrCreate([
            'bookkeeping_ledger_id' => $data['bookkeeping_ledger_id'],
            'transaction_type' => $data['transaction_type'],
            'person' => $data['Person'],
            'amount' => $data['Amount'],
            'other_information' => array_key_exists('Other Information', $data) ? json_encode($data['Other Information']) : null,
            'date' => $data['Date']
        ]);
    }

    public function storeMany(array $ledgers)
    {
        // Log::info('Storing ledgers in db');
        foreach ($ledgers as $ledger) {
            $this->model->firstOrCreate([
                'bookkeeping_ledger_id' => $ledger['bookkeeping_ledger_id'],
                'transaction_type' => $ledger['Transaction Type'],
                'person' => $ledger['Person'],
                'amount' => (string) $ledger['Amount'],
                'other_information' => array_key_exists('Other Information', $ledger) ? json_encode($ledger['Other Information']) : null,
                'date' => $ledger['Date']
            ]);
        }
        // Log::info('Ledgers stored successfully');
        return;
    }

    public function findAll(Reconciliation $reconciliation)
    {
        return $this->model->where('reconciliation_id', '=', $reconciliation->id)->get();
    }

    public function findAllByType(array $types)
    {
        $ledgers = [];
        foreach ($types as $type) {
            $typeLedgers = $this->model->where('bookkeeping_ledger_id', '=', $type)->get();
            $ledgers = [...$ledgers, ...$typeLedgers];
        }

        return collect($ledgers);
    }

    public function findById(string $id)
    {
        return $this->model->where('id', '=', $id)->with([
            'ledgerType',
            'payment',
            'match',
            'match.statement',
            'match.statement.statementFile',
            'match.statement.statementFile.bankAccount'
        ])->first();
    }

    public function addVector(Ledger $ledger, array $data)
    {
        if ($ledger->embedding == null) {
            $ledger->embedding = new Vector($data);
            $ledger->save();
        }

        return $ledger;
    }
}
