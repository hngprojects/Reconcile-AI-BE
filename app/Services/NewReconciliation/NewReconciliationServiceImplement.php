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
use Illuminate\Support\Facades\Response;

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

    public function storeReconciliation($statements, $ledgers, $user){
        DB::beginTransaction();

        $reconciliation = $this->mainRepository->store([
            'user_id' => $user,
            'option' => 'reconcile_with_Gemini'
        ]);

        foreach ($statements as $key => $value) {
            $statement = $this->fileRepository->store([
                'user_id' => $user,
                'file_name' => $value,
                'type' => 'Bank Statement'
            ]);
        }

        foreach ($ledgers as $key => $value) {
            $ledger = $this->fileRepository->store([
                'user_id' => $user,
                'file_name' => $value,
                'type' => 'Ledger'
            ]);
        }

        DB::commit();

        return $reconciliation;
    }

    protected function callGemini(string $prompt)
    {
        $client = new \GuzzleHttp\Client();
        // $apiKey = env('GEMINI_API_KEY');
        $apiKey = config('gemini.api_key');
        Log::info('Gemini API Key:', ['key' => config('gemini.api_key')]);

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
        $fileData = $this->loadComplexCsv($file);
        $data = $fileData['data'];
        $headers = json_encode($fileData['headers']);

        $descriptions = [];
        $structured = [];

        Log::info('CSV Headers: ', ['data' => $headers]);

        $prompt1 = "Which of these headers: {$headers} are best suited for the date and amount? The amount should be from a property that contains the amount that has been paid. Also which of the headers is most likely to contain the person's name or can be a unique identifier for the row. The unique identifier could be a student id, transaction id, reference id or any other synonyms that fit this criteria. Return your response in this format: { date_extracted_from: header, amount_extracted_from: header, name_likely_from: header }";

        $response = $this->callGemini("$prompt1. Please return only a valid JSON object");

        $cleanResponse = str_replace(["```json", "```"], "", $response);

        $decodedResponse = json_decode($cleanResponse, true);

        $dateHeader = array_key_exists('date_extracted_from', $decodedResponse) ? $decodedResponse['date_extracted_from'] : null;
        $amountHeader = array_key_exists('amount_extracted_from', $decodedResponse) ? $decodedResponse['amount_extracted_from'] : null;
        $nameHeader = array_key_exists('name_likely_from', $decodedResponse) ? $decodedResponse['name_likely_from'] : null;

        Log::info('Headers results', ['data' => $decodedResponse]);

        $excludeHeaders = [$dateHeader, $nameHeader, $amountHeader];
        Log::info('Excluded: ', ['data' => $excludeHeaders]);

        $otherHeaders = array_filter(json_decode($headers), function ($header) use ($excludeHeaders) {
            return !in_array(strtolower(trim($header)), array_map('strtolower', $excludeHeaders));
        });


        foreach ($data as $key) {
            if(str_contains(strtolower($nameHeader), 'name')) {
                $otherInfo = $this->getOtherData($key, $otherHeaders);
                $structured[] = [
                    'Person' => $key[$nameHeader],
                    'Date' => $dateHeader ? $key[$dateHeader] : null,
                    'Amount' => $key[$amountHeader],
                    'Other Information' => $otherInfo
                ];
            }else {
                $descriptions[] = $key[$nameHeader];
            }

        Log::info('Structured: ', ['data' => $structured]);

        if(!empty($descriptions)){
            $chunks = array_chunk($descriptions, 40);
            $names = [];
            $chunkIndex = 0;

            foreach ($chunks as $chunk) {
                $chunk = json_encode($chunk);
                $prompt2 = "Please extract the names from this JSON object: {$chunk}. Return a JSON object in the same order only in your response";

                $res = $this->callGemini("$prompt2. Please return only a valid JSON object");

                $cleanRes = str_replace(["```json", "```"], "", $res);

                $decodedRes = json_decode($cleanRes, true);

                Log::info('Headers results', ['data' => $decodedResponse]);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decodedRes)) {
                    foreach ($decodedRes as $key => $value) {
                        Log::info('key', ['data' => $key]);
                        Log::info('key', ['data' => $data]);
                        $otherInfo = $this->getOtherData($data[$key], $otherHeaders);
                        $structured[] = [
                            'Person' => $value,
                            'Date' => $dateHeader ? $data[$key][$dateHeader] : null,
                            'Amount' => $data[$key][$amountHeader],
                            'Other Information' => $otherInfo
                        ];
                    }
                } else {
                    Log::error('Failed to decode Gemini response for data2: ' . json_last_error_msg());
                }
                Sleep::for(5)->second();
            }
        }

        Log::info('Structured: ', ['data' => $structured]);
        return $structured;
        }
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

    protected function findMatchIndex(array $matches, $id)
    {
        foreach ($matches as $index => $match) {
            foreach ($match['statements'] as $statement) {
                Log::info('Statement: ', [$statement]);
                if ($statement['statement']['id'] === $id) {
                    return $index;
                }
            }
        }

        return false;
    }

    protected function generateEmbeddings(Collection $statements, Collection $ledgers){
        $statements->map(function (Statement $statement) {
            $formattedDate = date('Y-m-d', strtotime($statement->date));
            $formattedAmount = number_format($statement->amount, 2, '.', ',');

            $combinedText = "Person's name: {$statement->person}, Amount: {$formattedAmount} Date: {$formattedDate}, Other Relevant Information: {$ledger->other_information}";
            $embedding = $this->getEmbedding($combinedText);
            $this->statementRepository->addVector($statement, $embedding);
        });

        $ledgers->map(function (Ledger $ledger) {
            $formattedDate = date('Y-m-d', strtotime($ledger->date));
            $formattedAmount = number_format($ledger->amount, 2, '.', ',');

            $combinedText = "Person's name: {$ledger->person}, Amount: {$formattedAmount}, Date: {$formattedDate}, Other Relevant Information: {$ledger->other_information}";
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
            $percent = ceil($match->cosine_similarity * 100);
            $statementIndex = $this->findMatchIndex($matches, $match->statement_id);
            $ledgerIndex = $this->findMatchIndex($matches, $match->ledger_id);

            if($statementIndex){
                $matches[] = [
                    'statements' => [
                        [
                            'statement' => (new TransactionResource($this->statementRepository->findById($match->statement_id)))->toArray(request())
                        ]
                    ],
                    'ledgers' => [
                        ...matches[$statementIndex]['ledgers'],
                        [
                            'ledger' => (new TransactionResource($this->ledgerRepository->findById($match->ledger_id)))->toArray(request()),
                            'score' => "{$percent}%"
                        ]
                    ]
                ];
            }else if($ledgerIndex){
                $matches[] = [
                    'statements' => [
                        ...$matches[$ledgerIndex]['statements'],
                        [
                            'statement' => (new TransactionResource($this->statementRepository->findById($match->statement_id)))->toArray(request()),
                            'score' => "{$percent}%"
                        ]
                    ],
                    'ledgers' => [
                        [
                            'ledger' => (new TransactionResource($this->ledgerRepository->findById($match->ledger_id)))->toArray(request())
                        ]
                    ]
                ];
            }else{
                $matches[] = [
                    'statements' => [
                        [
                            'statement' => (new TransactionResource($this->statementRepository->findById($match->statement_id)))->toArray(request()),
                            'score' => "{$percent}%"
                        ]
                    ],
                    'ledgers' => [
                        [
                            'ledger' => (new TransactionResource($this->ledgerRepository->findById($match->ledger_id)))->toArray(request()),
                            'score' => "{$percent}%"
                        ]
                    ]
                ];
            }

            $matchedLedgerIds[] = $match->ledger_id;
            $matchedStatementIds[] = $match->statement_id;
        }
        Log::info('Matched Statements', ['data' => $matchedStatementIds]);
        Log::info('Matched Ledgers', ['data' => $matchedLedgerIds]);

        $unmatchedStatements = $statements
            ->whereNotIn('id', $matchedStatementIds)
            ->map(fn($stat) => (new TransactionResource($stat))->toArray(request()))
            ->values()
            ->all();

        $unmatchedLedgers = $ledgers
            ->whereNotIn('id', $matchedLedgerIds)
            ->map(fn($ledg) => (new TransactionResource($ledg))->toArray(request()))
            ->values()
            ->all();

        return [
            'reconciliation_id' => $statements->first()->reconciliation->id,
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

    public function usingEmbeddings(array $statements, array $ledgers, User $user, Reconciliation $reconciliation)
    {
        $structuredStatements = $this->structuringData($statements);
        $structuredLedgers = $this->structuringData($ledgers);

        $this->savingData($structuredStatements, $structuredLedgers, $reconciliation);

        $savedStatements = $this->statementRepository->findAll($reconciliation);
        $savedLedgers = $this->ledgerRepository->findAll($reconciliation);

        $this->generateEmbeddings($savedStatements, $savedLedgers);

        $response = $this->matchUsingEmbeddings($savedStatements, $savedLedgers);

        $record = $this->mainRepository->storeResponse([
            'reconciliation_id' => $reconciliation->id,
            'data' => $response
        ]);

        $file = $this->generateCSV($response);
        Mail::to($user->email)->send(new ReconciliationCompleted($reconciliation, $file, $user));

        return [
            'reconciliation_id' => $reconciliation->id,
            ...$response
        ];
    }

    public function export(Reconciliation $reconciliation){
        $record = $this->mainRepository->findResponse($reconciliation);

        $file = $this->generateCSV($record->data);

        return Response::download($file)->deleteFileAfterSend(true);

    }

    protected function generateCSV($data){
        $timestamp = now()->format('Y-m-d_H-i-s');
        $exportFileName = public_path("reconciled-data-" . now()->format('Y-m-d_H-i-s') . ".csv");

        $exportFile = fopen($exportFileName, 'w');
        fputcsv($exportFile, ['Bank Statement', '', '', '', '', 'Ledger', '', '']);
        fputcsv($exportFile, ['Date', 'Description', 'Amount', 'Status', 'Score', 'Date', 'Description', 'Amount']);

        foreach ($data['matches'] as $match) {
            $arr = [];
            if (count($match['statements']) == 1) {
                foreach ($match['ledgers'] as $key => $ledgerMatch) {
                    if($key == 0){
                        $arr = [
                            $match['statements'][0]['statement']['Date'],
                            $match['statements'][0]['statement']['Description'],
                            $match['statements'][0]['statement']['Amount'],
                            'Matched',
                            $ledgerMatch['score'],
                            $ledgerMatch['ledger']['Date'],
                            $ledgerMatch['ledger']['Description'],
                            $ledgerMatch['ledger']['Amount'],
                        ];
                    } else {
                        $arr = [
                            '', '', '',
                            'Matched',
                            $ledgerMatch['score'],
                            $ledgerMatch['ledger']['Date'],
                            $ledgerMatch['ledger']['Description'],
                            $ledgerMatch['ledger']['Amount'],
                        ];
                    }
                    fputcsv($exportFile, $arr);
                }
            }

            if (count($match['ledgers']) == 1) {
                foreach ($match['statements'] as $key => $statementMatch) {
                    Log::info('Index: ', ['key' => $key]);
                    if($key == 0){
                        $arr = [
                            $match['ledgers'][0]['ledger']['Date'],
                            $match['ledgers'][0]['ledger']['Description'],
                            $match['ledgers'][0]['ledger']['Amount'],
                            'Matched',
                            $statementMatch['score'],
                            $statementMatch['statement']['Date'],
                            $statementMatch['statement']['Description'],
                            $statementMatch['statement']['Amount'],
                        ];
                    } else {
                        $arr = [
                            '', '', '',
                            'Matched',
                            $statementMatch['score'],
                            $statementMatch['statement']['Date'],
                            $statementMatch['statement']['Description'],
                            $statementMatch['statement']['Amount'],
                        ];
                    }
                    fputcsv($exportFile, $arr);
                }
            }
        }

        foreach ($data['unmatched_statements'] as $row) {
            fputcsv($exportFile, [$row['Date'], $row['Description'], $row['Amount'], 'Unmatched']);
        }

        foreach($data['unmatched_ledgers'] as $row){
            $updated = ['', '', '','Unmatched', '', $row['Date'], $row['Description'], $row['Amount']];
            fputcsv($exportFile, $updated);
        }

        fclose($exportFile);

        return $exportFileName;
    }

    public function fetchResults(Reconciliation $reconciliation){
        $savedStatements = $this->statementRepository->findAll($reconciliation);
        $savedLedgers = $this->ledgerRepository->findAll($reconciliation);

        $matched = $this->matchedRepository->getMatches($reconciliation->id);

        $matchedStatementIds = [];
        $matchedLedgerIds = [];
        $matches = [];

        foreach ($matched as $match) {
            Log::info('Score: ', ['data' => $match]);
            $percent = $match['score'];
            $statementIndex = $this->findMatchIndex($matches, $match['statement_id']);
            $ledgerIndex = $this->findMatchIndex($matches, $match['ledger_id']);

            if($statementIndex){
                $matches[] = [
                    'statements' => [
                        [
                            'statement' => (new TransactionResource($this->statementRepository->findById($match['statement_id'])))->toArray(request())
                        ]
                    ],
                    'ledgers' => [
                        ...matches[$statementIndex]['ledgers'],
                        [
                            'ledger' => (new TransactionResource($this->ledgerRepository->findById($match['ledger_id'])))->toArray(request()),
                            'score' => "{$percent}%"
                        ]
                    ]
                ];
            }else if($ledgerIndex){
                $matches[] = [
                    'statements' => [
                        ...$matches[$ledgerIndex]['statements'],
                        [
                            'statement' => (new TransactionResource($this->statementRepository->findById($match['statement_id'])))->toArray(request()),
                            'score' => "{$percent}%"
                        ]
                    ],
                    'ledgers' => [
                        [
                            'ledger' => (new TransactionResource($this->ledgerRepository->findById($match['ledger_id'])))->toArray(request())
                        ]
                    ]
                ];
            }else{
                $matches[] = [
                    'statements' => [
                        [
                            'statement' => (new TransactionResource($this->statementRepository->findById($match['statement_id'])))->toArray(request()),
                            'score' => "{$percent}%"
                        ]
                    ],
                    'ledgers' => [
                        [
                            'ledger' => (new TransactionResource($this->ledgerRepository->findById($match['ledger_id'])))->toArray(request()),
                            'score' => "{$percent}%"
                        ]
                    ]
                ];
            }

            $matchedLedgerIds[] = $match['ledger_id'];
            $matchedStatementIds[] = $match['statement_id'];
        }
        Log::info('Matched Statements', ['data' => $matchedStatementIds]);
        Log::info('Matched Ledgers', ['data' => $matchedLedgerIds]);

        $unmatchedStatements = $savedStatements
            ->whereNotIn('id', $matchedStatementIds)
            ->map(fn($stat) => (new TransactionResource($stat))->toArray(request()))
            ->values()
            ->all();

        $unmatchedLedgers = $savedLedgers
            ->whereNotIn('id', $matchedLedgerIds)
            ->map(fn($ledg) => (new TransactionResource($ledg))->toArray(request()))
            ->values()
            ->all();

        return [
            'reconciliation_id' => $reconciliation->id,
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

    public function matchUnmatch(Reconciliation $reconciliation, array $statements, array $ledgers, string $action){
        if($action == 'match'){
            if(count($statements) == 1){
                foreach ($ledgers as $key => $ledger) {
                    $this->matchedRepository->storeByIds($ledger, $statements[0], 100);
                }
            }else if(count($ledgers) == 1){
                foreach ($statements as $key => $statement) {
                    $this->matchedRepository->storeByIds($ledgers[0], $statement, 100);
                }
            }
        }else if ($action == 'unmatch'){
            if(count($statements) == 1){
                foreach ($ledgers as $key => $ledger) {
                    $this->matchedRepository->removeByIds($ledger, $statements[0]);
                }
            }else if(count($ledgers) == 1){
                foreach ($statements as $key => $statement) {
                    $this->matchedRepository->removeByIds($ledgers[0], $statement);
                }
            }
        }

        return $this->fetchResults($reconciliation);
    }
}
