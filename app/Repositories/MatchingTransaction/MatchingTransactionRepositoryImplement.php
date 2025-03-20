<?php

namespace App\Repositories\MatchingTransaction;

use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\MatchingTransaction;
use App\Models\Ledger;
use App\Models\Statement;
use App\Models\Reconciliation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

    public function matchTransactions(Reconciliation $reconciliation){
        $matches = DB::select("
                SELECT DISTINCT ON (s.id)
                    s.id AS statement_id,
                    l.id AS ledger_id,
                    1 - (s.embedding <=> l.embedding) AS cosine_similarity
                FROM
                    statements s
                JOIN
                    ledgers l ON s.reconciliation_id = l.reconciliation_id
                WHERE
                    s.reconciliation_id = ?
                    AND 1 - (s.embedding <=> l.embedding) > 0.85
                ORDER BY
                    s.id, cosine_similarity DESC
            ", [$reconciliation->id]);

        foreach ($matches as $match) {
           $this->model->create([
                'ledger_id' => $match->ledger_id,
                'statement_id' => $match->statement_id,
                'score' => $match->cosine_similarity
            ]);
        }

        Log::info('Matching results', ['matches' => $matches]);
        return $matches;
    }
}
