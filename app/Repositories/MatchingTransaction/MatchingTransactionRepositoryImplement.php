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
        return $this->model
                    ->whereHas('statement', function ($query) use ($reconciliationId) {
                        $query->where('reconciliation_id', $reconciliationId);
                    })
                    ->orWhereHas('ledger.ledgerType.reconciliations', function ($query) use ($reconciliationId) {
                        $query->where('reconciliation_id', $reconciliationId);
                    })
                    ->with(['statement', 'ledger.bookkeepingLedger.reconciliations'])
                    ->select([
                        'matched_statements.statement_id',
                        'matched_statements.ledger_id',
                        'matched_statements.score'
                    ])
                    ->get();
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
                WITH ranked_matches AS (
                  SELECT
                    s.id AS statement_id,
                    l.id AS ledger_id,
                    1 - (s.embedding <=> l.embedding) AS cosine_similarity,
                    ROW_NUMBER() OVER (PARTITION BY s.id ORDER BY 1 - (s.embedding <=> l.embedding) DESC) AS statement_rank,
                    ROW_NUMBER() OVER (PARTITION BY l.id ORDER BY 1 - (s.embedding <=> l.embedding) DESC) AS ledger_rank
                  FROM statements s
                  JOIN ledgers l ON s.reconciliation_id = l.reconciliation_id
                  WHERE s.reconciliation_id = ?
                    AND 1 - (s.embedding <=> l.embedding) > 0.82
                )
                SELECT
                  statement_id,
                  ledger_id,
                  cosine_similarity
                FROM ranked_matches
                WHERE statement_rank = 1 AND ledger_rank = 1
                ORDER BY cosine_similarity DESC
            ", [$reconciliation->id]);

        /*
        $saved = [];

        foreach ($matches as $match) {
            // Log::info('Score: ', ['score' => $match->cosine_similarity]);
           $saved[] = $this->model->create([
                'ledger_id' => $match->ledger_id,
                'statement_id' => $match->statement_id,
                'score' => (int) ceil((float)$match->cosine_similarity * 100)
            ]);
        }
        */
        // Log::info('Matching results', ['matches' => $saved]);
        return $matches;
    }
}
