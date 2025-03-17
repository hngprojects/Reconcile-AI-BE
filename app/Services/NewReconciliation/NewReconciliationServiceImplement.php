<?php

namespace App\Services\NewReconciliation;

use LaravelEasyRepository\ServiceApi;
use App\Repositories\Reconciliation\ReconciliationRepository;
use App\Repositories\UserFile\UserFileRepository;
use App\Repositories\Ledger\LedgerRepository;
use App\Repositories\Statement\StatementRepository;
use App\Repositories\MatchingTransaction\MatchingTransactionRepository;
use App\Models\Reconciliation;
use App\Models\Statement;
use App\Models\Ledger;
use App\Models\User;
use App\Http\Resources\TransactionResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Support\Collection;

class NewReconciliationServiceImplement extends ServiceApi implements NewReconciliationService{

    /**
     * set title message api for CRUD
     * @param string $title
     */
     protected string $title = "";
     /**
     * uncomment this to override the default message
     * protected string $create_message = "";
     * protected string $update_message = "";
     * protected string $delete_message = "";
     */

     /**
     * don't change $this->mainRepository variable name
     * because used in extends service class
     */
    protected ReconciliationRepository $mainRepository;
    protected UserFileRepository $fileRepository;
    protected LedgerRepository $ledgerRepository;
    protected StatementRepository $statementRepository;
    protected MatchingTransactionRepository $matchedRepository;

    public function __construct(
        ReconciliationRepository $mainRepository,
        UserFileRepository $fileRepository,
        LedgerRepository $ledgerRepository,
        StatementRepository $statementRepository,
        MatchingTransactionRepository $matchedRepository
    )
    {
        $this->mainRepository = $mainRepository;
        $this->fileRepository = $fileRepository;
        $this->ledgerRepository = $ledgerRepository;
        $this->statementRepository = $statementRepository;
        $this->matchedRepository = $matchedRepository;
    }

    protected function loadComplexCsv($filePath)
    {
        $data = [];
        if (($handle = fopen($filePath, 'r')) !== false) {
            $headers = fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== false) {
                if(count(array_filter($headers)) < count($headers)/2){
                    $headers = $row;
                }else {
                    $data[] = array_combine($headers, $row);
                }
            }
            fclose($handle);
        }
        return $data;
    }

    protected function storeReconciliation($file1, $file2, $user){
        DB::beginTransaction();

        $statement = $this->fileRepository->store([
            'user_id' => $user,
            'file_name' => $file1,
            'type' => 'Bank Statement'
        ]);

        $ledger = $this->fileRepository->store([
            'user_id' => $user,
            'file_name' => $file2,
            'type' => 'Ledger'
        ]);

        $reconciliation = $this->mainRepository->store([
            'user_id' => $user,
            'option' => 'reconcile_with_Gemini'
        ]);

        DB::commit();

        return $reconciliation;
    }

    protected function isExactMatch(array $bankEntry, array $ledgerEntry): bool
    {
        return $bankEntry['amount'] === $ledgerEntry['amount'] &&
            strtolower($bankEntry['person']) === strtolower($ledgerEntry['person']);
    }

    protected function calculateFuzzyMatchScore(array $bankEntry, array $ledgerEntry): float
    {
        $amountDiff = abs((int)$bankEntry['amount'] - (int)$ledgerEntry['amount']);
        $amountScore = $amountDiff <= 0.5 ? (1 - $amountDiff / 0.5) * 50 : 0;

        similar_text(strtolower($bankEntry['person']), strtolower($ledgerEntry['person']), $descPercent);
        $descScore = $descPercent >= 75 ? $descPercent / 2 : 0;

        return $amountScore + $descScore;
    }

    protected function callGemini(string $prompt)
    {
        $client = new \GuzzleHttp\Client();
        $apiKey = env('GEMINI_API_KEY');

        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}";

        try {
            $response = $client->post($url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ]
                ],
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            return $body['candidates'][0]['content']['parts'][0]['text'] ?? json_encode($body);

        } catch (\Exception $e) {
            Log::error("Gemini API Error: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    protected function structuringData($file){
        $data = $this->loadComplexCsv($file);

        $chunks = array_chunk($data, 15);

        $structured = [];

        $prompt = "Please structure the attached JSON object into a JSON object with the following properties: Date, Person, Amount and Other Information. The JSON object could be a school ledger, an invoice ledger, a company ledger, a hospital ledger or a bank statement. Please keep this in mind as you go through the dataset. The person property can be derived from properties like Student Name, Student ID, Invoice ID, Invoice Detail, Narration, Summary, Remarks or any other synonyms that are used in a ledger or bank statement. Please ensure you derive a name and add it to the Person property. If it's not available add a short summary of the description instead. The amount can be derived from the debit, credit, amount, total, or anything that fits this criteria. Ensure the value for the amount that has been paid only so put into consideration any synonyms that may highlight this. Any other information should be added to the 'Other Information' property. Intelligently map through all the properties in the JSON and extract all the relevant information for this data structure. Use the relevant columns to extract this data and ensure the amount is always an absolute value and it should not have any symbols. Return all the data present in the provided JSON in JSON format. Please don't truncate the result.";

        foreach ($chunks as $chunk) {
            $response = $this->callGemini("$prompt. Here's the JSON you need to structure: " . json_encode($chunk) . ". Please return only a valid JSON object");

            $cleanResponse = str_replace(["```json", "```"], "", $response);

            $decodedResponse = json_decode($cleanResponse, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decodedResponse)) {
                $structured = array_merge($structured, $decodedResponse);
            } else {
                \Log::error('Failed to decode Gemini response for data2: ' . json_last_error_msg());
            }
        }

        return $structured;
    }

    protected function savingData(array $statements, array $ledgers, Reconciliation $reconciliation){
        DB::beginTransaction();

        $this->statementRepository->storeMany($statements, $reconciliation);
        $this->ledgerRepository->storeMany($ledgers, $reconciliation);

        DB::commit();
    }

    protected function findMatches(Reconciliation $reconciliation)
    {
        $statements = $this->statementRepository->findAll($reconciliation);
        $ledgers = $this->ledgerRepository->findAll($reconciliation);

        $matches = [];
        $matchedStatementIds = [];
        $matchedLedgerIds = [];

        foreach ($statements as $statement) {
            foreach ($ledgers as $ledger) {
                $score = $this->calculateFuzzyMatchScore($statement->toArray(), $ledger->toArray());

                if ($this->isExactMatch($statement->toArray(), $ledger->toArray())) {
                    $matches[] = [
                        'statement' => (new TransactionResource($statement))->toArray(request()),
                        'ledger' => (new TransactionResource($ledger))->toArray(request()),
                        'score' => 100,
                    ];
                    $newMatch = $this->matchedRepository->store($ledger, $statement);

                    $matchedStatementIds[] = $statement->id;
                    $matchedLedgerIds[] = $ledger->id;
                    break;
                } else if ($score > 70) {
                    $matches[] = [
                        'statement' => (new TransactionResource($statement))->toArray(request()),
                        'ledger' => (new TransactionResource($ledger))->toArray(request()),
                        'score' => $score,
                    ];
                    $newMatch = $this->matchedRepository->store($ledger, $statement);

                    $matchedStatementIds[] = $statement->id;
                    $matchedLedgerIds[] = $ledger->id;
                    break;
                }
            }
        }

        $unmatchedStatements = $statements->filter(function ($statement) use ($matchedStatementIds) {
            return !in_array($statement->id, $matchedStatementIds);
        })->map(function ($statement) {
            return (new TransactionResource($statement))->toArray(request());
        })->values()->toArray();

        $unmatchedLedgers = $ledgers->filter(function ($ledger) use ($matchedLedgerIds) {
            return !in_array($ledger->id, $matchedLedgerIds);
        })->map(function ($ledger) {
            return (new TransactionResource($ledger))->toArray(request());
        })->values()->toArray();

        return [
            'reconciliation_id' => $reconciliation->id,
            'matches' => $matches,
            'unmatched_statements' => $unmatchedStatements,
            'unmatched_ledgers' => $unmatchedLedgers,
        ];
    }

    protected function getEmbedding(string $text){
        $response = Gemini::embeddingModel()->embedContent($text);

        return $response->embedding->values;
    }

    protected function generateEmbeddings(Collection $statements, Collection $ledgers){
        $statements->map(function (Statement $statement) {
            $combinedText = "Name: {$statement->person}, Amount: {$statement->amount}, Description: {$statement->other_information} Date: {$statement->date}";
            $embedding = $this->getEmbedding($combinedText);
            $this->statementRepository->addVector($statement, $embedding);
        });

        $ledgers->map(function (Ledger $ledger) {
            $combinedText = "Name: {$ledger->person}, Amount: {$ledger->amount}, Description: {$ledger->other_information} Date: {$ledger->date}";
            $embedding = $this->getEmbedding($combinedText);
            $this->ledgerRepository->addVector($ledger, $embedding);
        });
    }

    protected function cosineSimilarity($embeddingA, $embeddingB)
    {
        $dotProduct = 0;
        $magnitudeA = 0;
        $magnitudeB = 0;

        for ($i = 0; $i < count($embeddingA); $i++) {
            $dotProduct += $embeddingA[$i] * $embeddingB[$i];
            $magnitudeA += $embeddingA[$i] * $embeddingA[$i];
            $magnitudeB += $embeddingB[$i] * $embeddingB[$i];
        }

        $magnitudeA = sqrt($magnitudeA);
        $magnitudeB = sqrt($magnitudeB);

        return $dotProduct / ($magnitudeA * $magnitudeB);
    }

    protected function matchUsingEmbeddings(Collection $statements, Collection $ledgers)
    {
        $matches = [];
        $unmatchedLedgers = [];
        $unmatchedStatements = [];

        foreach ($ledgers as $ledger) {
            $bestMatch = null;
            $bestScore = -1;

            foreach ($statements as $statement) {
                $ledgerEmbedding = json_decode($ledger->embedding, true);
                $statementEmbedding = json_decode($statement->embedding, true);

                $score = $this->cosineSimilarity($ledgerEmbedding, $statementEmbedding);

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestMatch = $statement;
                }
            }

            if ($bestScore > 0.9) {
                $matches[] = [
                    'ledger' => (new TransactionResource($ledger))->toArray(request()),
                    'statement' => (new TransactionResource($bestMatch))->toArray(request()),
                    'score' => $bestScore,
                ];

                $this->matchedRepository->store($ledger, $bestMatch);
            } else {
                $unmatchedLedgers[] = (new TransactionResource($ledger))->toArray(request());
            }
        }

        $matchedStatementIds = collect($matches)->pluck('statement.id')->toArray();
        $unmatchedStatements = $statements->whereNotIn('id', $matchedStatementIds)->map(fn($stat) => (new TransactionResource($stat))->toArray(request()));

        return [
            'matches' => $matches,
            'unmatched_ledgers' => $unmatchedLedgers,
            'unmatched_statements' => $unmatchedStatements,
        ];
    }

    public function usingEmbeddings(string $statement, string $ledger, User $user)
    {
        $reconciliation = $this->storeReconciliation($statement, $ledger, $user->id);

        $structuredStatements = $this->structuringData($statement);
        $structuredLedgers = $this->structuringData($ledger);

        $this->savingData($structuredStatements, $structuredLedgers, $reconciliation);

        $statements = $this->statementRepository->findAll($reconciliation);
        $ledgers = $this->ledgerRepository->findAll($reconciliation);

        $this->generateEmbeddings($statements, $ledgers);

        $response = $this->matchUsingEmbeddings($statements, $ledgers);

        $record = $this->mainRepository->storeResponse([
            'reconciliation_id' => $reconciliation->id,
            'data' => $response
        ]);

        return [
            'reconciliation_id' => $reconciliation->id,
            ...$response
        ];
    }

    public function usingRecox(string $statement, string $ledger, User $user)
    {
        $reconciliation = $this->storeReconciliation($statement, $ledger, $user->id);

        $structuredStatements = $this->structuringData($statement);
        $structuredLedgers = $this->structuringData($ledger);

        $this->savingData($structuredStatements, $structuredLedgers, $reconciliation);

        $response = $this->findMatches($reconciliation);
        $record = $this->mainRepository->storeResponse([
            'reconciliation_id' => $reconciliation->id,
            'data' => $response
        ]);

        return $response;
    }

}
