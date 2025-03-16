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

    protected function storeReconciliation($file1, $file2){
        DB::beginTransaction();

        $statement = $this->fileRepository->store([
            'user_id' => Auth::id(),
            'file_name' => $file1,
            'type' => 'Bank Statement'
        ]);

        $ledger = $this->fileRepository->store([
            'user_id' => Auth::id(),
            'file_name' => $file2,
            'type' => 'Ledger'
        ]);

        $reconciliation = $this->mainRepository->store([
            'user_id' => Auth::id(),
            'option' => 'reconcile_with_Gemini'
        ]);

        DB::commit();

        return $reconciliation;
    }

    protected function isExactMatch(array $bankEntry, array $ledgerEntry): bool
    {
        return $bankEntry['amount'] === $ledgerEntry['amount'] &&
            strtolower($bankEntry['description']) === strtolower($ledgerEntry['description']);
    }

    protected function calculateFuzzyMatchScore(array $bankEntry, array $ledgerEntry): float
    {
        $amountDiff = abs($bankEntry['amount'] - $ledgerEntry['amount']);
        $amountScore = $amountDiff <= 0.5 ? (1 - $amountDiff / 0.5) * 50 : 0;

        similar_text(strtolower($bankEntry['description']), strtolower($ledgerEntry['description']), $descPercent);
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

        $prompt = "Please structure the attached JSON object into a JSON object with the following properties: Date, Person, Amount and Other Information. The JSON object could be a school ledger, an invoice ledger, a company ledger, a hospital ledger or a bank statement. Please keep this in mind as you go through the dataset. The person property can be derived from properties like Student Name, Student ID, Invoice ID, Invoice Detail, Narration, Summary, Remarks or any other synonyms that are used in a ledger or bank statement. Please only include the exact value provided in the document only. The amount can be derived from the debit, credit, amount, total, or anything that fits this criteria. Ensure the value for the amount that has been paid only so put into consideration any synonyms that may highlight this. Any other information should be added to the 'Other Information' property. Intelligently map through all the properties in the JSON and extract all the relevant information for this data structure. Use the relevant columns to extract this data and ensure the amount is always an absolute value and it should not have any symbols. Return all the data present in the provided JSON in JSON format. Please don't truncate the result.";

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

    protected function findMatches(Reconciliation $reconciliation){
        $statements = $this->statementRepository->findAll($reconciliation);
        $ledgers = $this->ledgerRepository->findAll($reconciliation);

        $statementChunks = array_chunk($statements->toArray(), 20);
        $ledgerChunks = array_chunk($ledgers->toArray(), 20);

        $matches = [];
        $unmatchedStatements = [];
        $unmatchedLedgers = [];

        foreach ($statementChunks as $sChunk) {
            foreach ($sChunk as $statement) {
                foreach ($ledgerChunks as $lChunk) {
                    foreach ($lChunk as $ledger) {
                        $score = $this->calculateFuzzyMatchScore($statement, $ledger);

                        if ($this->isExactMatch($statement, $ledger)) {
                            $matches[] = [
                                'file1_transaction' => $statement,
                                'file2_transaction' => $ledger,
                                'match_score' => 100,
                            ];
                        $newMatch = $this->matchedRepository->store($ledger, $statement);
                            break;
                        }else if ($score > 70) {
                            $matches[] = [
                                'file1_transaction' => $statement,
                                'file2_transaction' => $ledger,
                                'match_score' => $score,
                            ];
                            $newMatch = $this->matchedRepository->store($ledger, $statement);
                            break;
                        }else {
                            $unmatchedStatements[] = $statement;
                            $unmatchedLedgers[] = $ledger;
                        }
                    }
                }
            }
        }

        return [
            'reconciliation_id' => $reconciliation->id,
            'matches' => $matches,
            'unmatched_statements' => $unmatchedStatements,
            'unmatched_ledgers' => $unmatchedLedgers
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
            $this->statementRepository->addVectore($statement, $embedding);
        });

        $ledgers->map(function (Ledger $ledger) {
            $combinedText = "Name: {$ledger->person}, Amount: {$ledger->amount}, Description: {$ledger->other_information} Date: {$ledger->date}";
            $embedding = $this->getEmbedding($combinedText);
            $this->statementRepository->addVectore($ledger, $embedding);
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

    protected function matchUsingEmbeddings(Collection $statements, Collection $ledgers){
        $matches = [];
        $unmatchedLedgers = [];
        $unmatchedStatements = [];

        foreach ($ledgers as $ledger) {
            $bestMatch = null;
            $bestScore = PHP_FLOAT_MAX;

            foreach ($statements as $statement) {
                $ledgerEmbedding = json_decode($ledger->embedding, true);
                $statementEmbedding = json_decode($statement->embedding, true);

                $score = $this->cosineSimilarity($ledgerEmbedding, $statementEmbedding);

                if ($score < $bestScore) {
                    $bestScore = $score;
                    $bestMatch = $statement;
                }
            }

            if ($bestScore < 0.2) {
                $matches[] = [
                    'ledger' => $ledger,
                    'statement' => $bestMatch,
                    'score' => $bestScore,
                ];

                $newMatch = $this->matchedRepository->store($ledger, $statement);
            } else {
                $unmatchedLedgers[] = $ledger;
            }
        }

        $matchedStatementIds = collect($matches)->pluck('statement.id')->toArray();
        $unmatchedStatements = $statements->whereNotIn('id', $matchedStatementIds);

        return [
            'matches' => $matches,
            'unmatched_ledgers' => $unmatchedLedgers,
            'unmatched_statements' => $unmatchedStatements,
        ];
    }

    public function usingEmbeddings($statement, $ledger)
    {
        $reconciliation = $this->storeReconciliation($statement, $ledger);

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

        return $response;
    }

    public function usingRecox($statement, $ledger)
    {
        $reconciliation = $this->storeReconciliation($statement, $ledger);

        $structuredStatements = $this->structuringData($statement);
        $structuredLedgers = $this->structuringData($ledger);

        $this->savingData($structuredStatements, $structuredLedgers, $reconciliation);

        return $this->findMatches($reconciliation);
    }

}
