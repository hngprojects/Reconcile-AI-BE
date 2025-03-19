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
use Illuminate\Support\Facades\Log;
use App\Mail\ReconciliationCompleted;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Sleep;

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
        $headers = [];

        if (($handle = fopen($filePath, 'r')) !== false) {
            while (($row = fgetcsv($handle)) !== false) {
                if (count(array_filter($row)) === 0) {
                    continue;
                }

                $filtered = array_filter($headers, function ($item) {
                    return $item !== "" && $item !== "#N/A";
                });

                $filteredRow = array_filter($row, function ($item) {
                    return $item === "" || $item === "#N/A";
                });

                if (empty($headers) || count($filtered) != count($row)) {
                    $headers = $row;
                    continue;
                }

                if (count($headers) === count($row) && count($filteredRow) !== count($headers)) {
                    $data[] = array_combine($headers, $row);
                } else {
                    Log::warning('Row does not match header count: ', ['row' => $row]);
                }
            }

            fclose($handle);
        } else {
            throw new \Exception('Unable to open the CSV file.');
        }

        return ['data' => $data, 'headers' => $headers];
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

    protected function callGemini(string $prompt)
    {
        $client = new \GuzzleHttp\Client();
        $apiKey = env('GEMINI_API_KEY');
        Log::info('Gemini API Key:', ['key' => env('GEMINI_API_KEY')]);

        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-lite:generateContent?key={$apiKey}";

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

    protected function getOtherData($d, $otherHeaders) {
            $res = [];
            foreach ($otherHeaders as $key) {
                if(array_key_exists($key, $d)){
                    $res[$key] = $d[$key];
                }
            }

            return $res;
        }

    protected function structuringData($file){
        $data = $this->loadComplexCsv($file);

        $chunks = array_chunk($data, 15);

        $structured = [];

        $prompt = "Please structure the attached JSON object into a JSON object with the following properties: Date, Person, Amount and Other Information. The JSON object could be a school ledger, an invoice ledger, a company ledger, a hospital ledger or a bank statement. Please keep this in mind as you go through the dataset. The person property can be derived from properties like Student Name, Student ID, Invoice ID, Invoice Detail, Narration, Summary, Remarks or any other synonyms that are used in a ledger or bank statement. Please ensure you derive a name and add it to the Person property. If it's not available add a short summary of the description instead or the unique identifier. The amount can be derived from the debit, credit, amount, total, or anything that fits this criteria. Ensure the value for the amount that has been paid only so put into consideration any synonyms that may highlight this. Any other information should be added to the 'Other Information' property. Intelligently map through all the properties in the JSON and extract all the relevant information for this data structure. Please exclude any rows that have no data. Use the relevant columns to extract this data and ensure the amount is always an absolute value and it should not have any symbols. Return all the data present in the provided JSON in JSON format. Please don't truncate the result.";

        foreach ($chunks as $chunk) {
            $response = $this->callGemini("$prompt. Here's the JSON you need to structure: " . json_encode($chunk) . ". Please return only a valid JSON object");

            $cleanResponse = str_replace(["```json", "```"], "", $response);

            $decodedResponse = json_decode($cleanResponse, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decodedResponse)) {
                $structured = array_merge($structured, $decodedResponse);
            } else {
                Log::error('Failed to decode Gemini response for data2: ' . json_last_error_msg());
            }

            Sleep::for(2.5)->seconds();
        }

        return $structured;
    }

    protected function savingData(array $statements, array $ledgers, Reconciliation $reconciliation){
        DB::beginTransaction();

        $this->statementRepository->storeMany($statements, $reconciliation);
        $this->ledgerRepository->storeMany($ledgers, $reconciliation);

        DB::commit();
    }

    protected function getEmbedding(string $text){
        $response = Gemini::embeddingModel()->embedContent($text);

        return $response->embedding->values;
    }

    protected function generateEmbeddings(Collection $statements, Collection $ledgers){
        $statements->map(function (Statement $statement) {
            $combinedText = "{$statement->person}, {$statement->amount},{$statement->other_information}, {$statement->date}";
            $embedding = $this->getEmbedding($combinedText);
            $this->statementRepository->addVector($statement, $embedding);
        });

        $ledgers->map(function (Ledger $ledger) {
            $combinedText = "{$ledger->person}, {$ledger->amount}, {$ledger->other_information}, {$ledger->date}";
            $embedding = $this->getEmbedding($combinedText);
            $this->ledgerRepository->addVector($ledger, $embedding);
        });
    }

    protected function matchUsingEmbeddings(Collection $statements, Collection $ledgers)
    {
        $matches = [];
        $unmatchedLedgers = [];
        $unmatchedStatements = [];

        $matched = $this->matchedRepository->matchTransactions($statements->first()->reconciliation);
        $matchedStatementIds = [];
        $matchedLedgerIds = [];

        foreach ($matched as $match) {
            $percent = $match->cosine_similarity * 100;
            $matches[] = [
                'statement' => (new TransactionResource($this->statementRepository->findById($match->statement_id)))->toArray(request()),
                'ledger' => (new TransactionResource($this->ledgerRepository->findById($match->ledger_id)))->toArray(request()),
                'score' => "{$percent}%"
            ];
            $matchedLedgerIds[] = $match->ledger_id;
            $matchedStatementIds[] = $match->statement_id;
        }
        Log::info('Matched Statements', ['data' => $matchedStatementIds]);
        Log::info('Matched Ledgers', ['data' => $matchedLedgerIds]);
        $unmatchedStatements = $statements->whereNotIn('id', $matchedStatementIds)->map(fn($stat) => (new TransactionResource($stat))->toArray(request()));
        $unmatchedLedgers = $ledgers->whereNotIn('id', $matchedLedgerIds)->map(fn($ledg) => (new TransactionResource($ledg))->toArray(request()));

        return [
            'matches' => $matches,
            'unmatched_ledgers' => $unmatchedLedgers,
            'unmatched_statements' => $unmatchedStatements,
            'summary' => [
                'totalMatched' => count($matches),
                'totalUnmatched' => count($unmatchedLedgers) + count($unmatchedStatements),
                'total' => count($unmatchedLedgers) + count($unmatchedStatements) + count($matches)
            ]
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

        $file = $this->generateCSV($response);
        Mail::to($user->email)->send(new ReconciliationCompleted($reconciliation, $file));

        return [
            'reconciliation_id' => $reconciliation->id,
            ...$response
        ];
    }

    protected function generateCSV($data){
        $timestamp = now()->format('Y-m-d_H-i-s');
        $exportFileName = public_path("reconciled-data-" . now()->format('Y-m-d_H-i-s') . ".csv");

        $exportFile = fopen($exportFileName, 'w');
        fputcsv($exportFile, ['Bank Statement', '', '', '', 'Ledger']);
        fputcsv($exportFile, ['Date', 'Description', 'Amount', 'Status', 'Date', 'Description', 'Amount']);

        foreach ($data['matches'] as $row) {
            $rowData = [
                ...$row['statement'],
                'Matched'
            ];
            foreach ($row['ledger'] as $value) {
                array_push($rowData, $value);
            }
            fputcsv($exportFile, $rowData);
        }

        foreach ($data['unmatched_statements'] as $row) {
            fputcsv($exportFile, [...$row, 'Unmatched']);
        }

        foreach($data['unmatched_ledgers'] as $row){
            $updated = ['', '', '', 'Unmatched', ...$row];
            fputcsv($exportFile, $updated);
        }

        fclose($exportFile);

        return $exportFileName;
    }
}
