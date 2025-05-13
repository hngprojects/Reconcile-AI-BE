<?php

namespace App\Repositories\Statement;

use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\Statement;
use App\Models\Reconciliation;
use Pgvector\Laravel\Vector;
use Illuminate\Support\Facades\Log;

class StatementRepositoryImplement extends Eloquent implements StatementRepository
{

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

    public function store(array $data)
    {
        return $this->model->firstOrCreate([
            'reconciliation_id' => $data['reconciliation_id'],
            'person' => $data['Person'],
            'amount' => $data['Amount'],
            'other_information' => array_key_exists('Other Information', $data) ? json_encode($data['Other Information']) : null,
            'date' => $data['Date'],
            'statement_file_id' => $data['statement_file_id']
        ]);
    }

    public function storeMany(array $statements, Reconciliation $reconciliation)
    {
        // Log::info('Storing statements in db');
        foreach ($statements as $statement) {
            $this->model->firstOrCreate([
                'reconciliation_id' => $reconciliation->id,
                'person' => $statement['Person'],
                'amount' => (string) $statement['Amount'],
                'other_information' => array_key_exists('Other Information', $statement) ? json_encode($statement['Other Information']) : null,
                'date' => $statement['Date'],
                'statement_file_id' => $statement['statement_file_id'] ?? null
            ]);
        }
        // Log::info('Statements stored successfully');
        return;
    }

    public function findAll(Reconciliation $reconciliation)
    {
        return Statement::where('reconciliation_id', '=', $reconciliation->id)->get();
    }

    public function findById(string $id)
    {
        return Statement::where('id', '=', $id)->with(['statementFile', 'statementFile.bankAccount'])->first();
    }

    public function addVector(Statement $statement, array $data)
    {
        $statement->embedding = new Vector($data);
        $statement->save();

        return $statement;
    }
}
