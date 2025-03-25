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

    public function storeByIds(string $ledger, string $statement, int $score){
        return $this->model->create([
            'ledger_id' => $ledger,
            'statement_id' => $statement,
            'score' => $score
        ]);
    }

    public function getMatches(string $reconciliationId) {
        return $this->model->whereHas('statement', function ($query) use ($reconciliationId) {
                $query->where('reconciliation_id', $reconciliationId);
            })
            ->orWhereHas('ledger', function ($query) use ($reconciliationId) {
                $query->where('reconciliation_id', $reconciliationId);
            })
            ->with(['statement', 'ledger'])
            ->select(['matched_statements.statement_id', 'matched_statements.ledger_id', 'matched_statements.score'])->get();
    }

    public function remove(Ledger $ledger, Statement $statement){
        $match = $this->model->where([
            'ledger_id' => $ledger->id,
            'statement_id' => $statement->id,
        ])->first();
        if($match){
            return $match->delete();
        }

        return;
    }

    public function removeByIds(string $ledger, string $statement){
        $match = $this->model->where([
            'ledger_id' => $ledger,
            'statement_id' => $statement,
        ])->first();
        if($match){
            return $match->delete();
        }

        return;
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
                    AND 1 - (s.embedding <=> l.embedding) > 0.82
                ORDER BY
                    s.id, cosine_similarity DESC
            ", [$reconciliation->id]);

        foreach ($matches as $match) {
            Log::info('Score: ', ['score' => $match->cosine_similarity]);
           $this->model->create([
                'ledger_id' => $match->ledger_id,
                'statement_id' => $match->statement_id,
                'score' => (int) $match->cosine_similarity
            ]);
        }

        Log::info('Matching results', ['matches' => $matches]);
        return $matches;
    }
}
