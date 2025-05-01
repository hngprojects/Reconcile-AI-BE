<?php

namespace App\Services\NewReconciliation;

use LaravelEasyRepository\ServiceApi;
use App\Repositories\Reconciliation\ReconciliationRepository;
use App\Repositories\UserFile\UserFileRepository;
use App\Repositories\Ledger\LedgerRepository;
use App\Repositories\Statement\StatementRepository;
use App\Repositories\StatementFile\StatementFileRepository;
use App\Repositories\MatchingTransaction\MatchingTransactionRepository;
use App\Repositories\LedgerPayment\LedgerPaymentRepository;
use App\Models\Reconciliation;
use App\Models\ReconciledRecord;
use App\Models\Statement;
use App\Models\StatementFile;
use App\Models\Ledger;
use App\Models\BookkeepingLedger;
use App\Models\User;
use App\Http\Resources\TransactionResource;
use Illuminate\Support\Facades\DB;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use App\Mail\ReconciliationCompleted;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Sleep;
use Illuminate\Support\Facades\Response;
use NumConvert;
use App\Events\ReconciliationProgressUpdated;
use Illuminate\Support\Arr;

class NewReconciliationServiceImplement extends ServiceApi implements NewReconciliationService
{

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
    protected LedgerPaymentRepository $ledgerPaymentRepository;
    protected StatementRepository $statementRepository;
    protected StatementFileRepository $statementFileRepository;
    protected MatchingTransactionRepository $matchedRepository;

    public function __construct(
        ReconciliationRepository $mainRepository,
        UserFileRepository $fileRepository,
        LedgerRepository $ledgerRepository,
        LedgerPaymentRepository $ledgerPaymentRepository,
        StatementRepository $statementRepository,
        StatementFileRepository $statementFileRepository,
        MatchingTransactionRepository $matchedRepository
    ) {
        $this->mainRepository = $mainRepository;
        $this->fileRepository = $fileRepository;
        $this->ledgerRepository = $ledgerRepository;
        $this->ledgerPaymentRepository = $ledgerPaymentRepository;
        $this->statementRepository = $statementRepository;
        $this->statementFileRepository = $statementFileRepository;
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

        return $data;
    }

    public function storeReconciliation($statements, $ledgers, $title, $userId)
    {
        DB::beginTransaction();

        $reconciliation = $this->mainRepository->store([
            'user_id' => $userId,
            'title' => $title,
            'status' => 'draft',
        ]);

        $statementFileIds = [];

        foreach ($statements as $statementData) {
            $savedFile = $this->fileRepository->store([
                'user_id' => $userId,
                'file_name' => $statementData['name'],
                'file_path' => $statementData['path'],
                'type' => 'Statement'
            ]);

            $statementFile = $this->statementFileRepository->store([
                'user_file_id' => $savedFile->id,
                ...collect($statementData)->except([
                    'period'
                ])->toArray(),
                ...$statementData['period']
            ]);

            $statementFileIds[] = $statementFile->id;
        }

        $reconciliation->statementFiles()->attach($statementFileIds);

        $ledgerIds = BookkeepingLedger::whereIn('id', $ledgers)->pluck('id');
        $reconciliation->ledgers()->sync($ledgerIds);

        DB::commit();

        return $reconciliation;
    }

    public function saveLedger(string $filePath, User $user)
    {
        $filePath = str_replace('public/', '', $filePath);
        $filePath = str_replace('storage/', '', $filePath);

        // Log::info('File Path: ', ['path' => $filePath]);

        if (!file_exists($filePath)) {
            throw new \Exception("File not found: {$filePath}");
        }

        // Log::info('Saving Ledger: ', ['user' => $user->id]);
        $ledger = $this->fileRepository->store([
            'user_id' => $user,
            'file_name' => $filePath,
            'type' => 'Ledger'
        ]);
    }

    protected function callGemini(string $prompt)
    {
        $client = new \GuzzleHttp\Client();
        // $apiKey = env('GEMINI_API_KEY');
        $apiKey = config('gemini.api_key');
        //Log::info('Gemini API Key:', ['key' => config('gemini.api_key')]);

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

    protected function getOtherData($d, $otherHeaders)
    {
        $res = [];
        foreach ($otherHeaders as $key) {
            if (array_key_exists($key, $d)) {
                $res[$key] = $d[$key];
            }
        }

        return $res;
    }

    protected function structuringData($files)
    {
        $structured = [];
        // Log::info('Starting data structuring....', ['files' => $files]);

        foreach ($files as $file) {
            $data = $this->loadComplexCsv($file);
            // Log::info('Loaded data: ', ['files' => $data]);

            $chunks = array_chunk($data, 10);

            $prompt = "Please structure the attached JSON object into a JSON object with the following properties: Date, Person, Amount, Transaction Type and Other Information. The JSON object could be a school ledger, an invoice ledger, a company ledger, a hospital ledger or a bank statement. Please keep this in mind as you go through the dataset. The person property can be derived from properties like Student Name, Invoice Detail, Narration, Summary, Remarks or any other synonyms that are used in a ledger or bank statement. Please ensure you derive a name and add it to the Person property. If it's not available, provide a short summary of the provided data or the unique identifier such as invoice ID and transaction codes or any other synonym. The person property should never be an empty string and it can't be N/A as well. The trasaction type can be either an Expense, Income, Liability or Asset. You cannot assign any other value to this property. The amount can be derived from the debit, credit, amount, total, or anything that fits this criteria. Ensure the value for the amount that has been paid only so put into consideration any synonyms that may highlight this. Any other information should be added to the 'Other Information' property. Intelligently map through all the properties in the JSON and extract all the relevant information for this data structure. Please exclude any rows that have no data. Use the relevant columns to extract this data and ensure the amount is always an absolute value and it should not have any symbols. Return all the data present in the provided JSON in JSON format. Please don't truncate the result.";

            foreach ($chunks as $chunk) {
                // Log::info('chunk size: ', ['size' => count($chunks)]);
                // Log::info('Calling Gemini......');
                $response = $this->callGemini("$prompt. Here's the JSON you need to structure: " . json_encode($chunk) . ". Please return only a valid JSON object");

                $cleanResponse = str_replace(["```json", "```"], "", $response);
                // Log::info('Info: ', ['res' => $cleanResponse]);

                $decodedResponse = json_decode($cleanResponse, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($decodedResponse)) {
                    $structured = array_merge($structured, $decodedResponse);
                } else {
                    Log::error('Failed to decode Gemini response for data2: ' . json_last_error_msg());
                }

                // Log::info('Sleeping for 2 seconds....');
                Sleep::for(2.5)->seconds();
            }
        }

        return $structured;
    }

    protected function mappingData(array $files, array $mapper)
    {
        $mapped = [];

        foreach ($files as $file) {
            $data = $this->loadComplexCsv(gettype($file) == 'array' ? $file['path'] : $file);
            foreach ($data as $row) {
                $mapped[] = [
                    'Date' => $row[$mapper['date']],
                    'Person' => $row[$mapper['description']],
                    'Amount' => $row[$mapper['amount']],
                    'Other Information' => collect($row)->except([
                        $mapper['date'],
                        $mapper['description'],
                        $mapper['amount']
                    ])->toArray(),
                ];
            }
        }

        return $mapped;
    }


    protected function mappingStatement(array $stmt, array $mapper)
    {
        $mapped = [];

        $data = $this->loadComplexCsv($stmt['path']);
        foreach ($data as $row) {
            $mapped[] = [
                'Date' => $row[$mapper['date']],
                'Person' => $row[$mapper['description']],
                'Amount' => $row[$mapper['amount']],
                'Other Information' => collect($row)->except([
                    $mapper['date'],
                    $mapper['description'],
                    $mapper['amount']
                ])->toArray(),
            ];
        }


        return $mapped;
    }

    protected function savingStatements(array $statements, Reconciliation $reconciliation)
    {
        $this->statementRepository->storeMany($statements, $reconciliation);
    }

    protected function savingLedgers(array $ledgers)
    {
        $this->ledgerRepository->storeMany($ledgers);
    }

    protected function getEmbedding(string $text)
    {
        $response = Gemini::embeddingModel()->embedContent($text);

        return $response->embedding->values;
    }

    protected function findMatchIndex(array $matches, $id)
    {
        foreach ($matches as $index => $match) {
            foreach ($match['statements'] as $statement) {
                // Log::info('Statement: ', [$statement]);
                if ($statement['statement']['id'] === $id) {
                    return $index;
                }
            }
        }

        return false;
    }

    protected function generateStatementEmbeddings(Collection $statements)
    {
        $statements->map(function (Statement $statement) {
            $formattedDate = date('Y-m-d', strtotime($statement->date));
            $amt = NumConvert::word($statement->amount);
            $combinedText = "Person's name: {$statement->person}, Amount: {$statement->amount}, Amount in words: {$amt} Date: {$formattedDate}, Other Relevant Information: {$statement->other_information}";
            $embedding = $this->getEmbedding($combinedText);
            $this->statementRepository->addVector($statement, $embedding);
        });
    }

    protected function generateLedgerEmbeddings(Collection $ledgers)
    {
        $ledgers->map(function (Ledger $ledger) {
            $payment = $this->ledgerPaymentRepository->findByLedger($ledger);
            $formattedDate = date('Y-m-d', strtotime($ledger->date));
            $amt = NumConvert::word($ledger->amount);
            $paymentStr = $payment !== null ? ", Amount Paid: {$payment->amount_paid}, Payment Status: {$payment->payment_status}, Bank Account: {$payment->account->bank_name}" : "";
            $combinedText = "Person's name: {$ledger->person}, Amount: {$ledger->amount}, Amount in words: {$amt}, Date: {$formattedDate} Other Relevant Information: {$ledger->other_information}" . $paymentStr;
            $embedding = $this->getEmbedding($combinedText);
            $this->ledgerRepository->addVector($ledger, $embedding);
        });
    }

    protected function matchUsingEmbeddings(Collection $statements, Collection $ledgers)
    {
        $unmatchedLedgers = [];
        $unmatchedStatements = [];

        $matched = $this->matchedRepository->matchTransactions($statements->first()->reconciliation);
        $matchedStatementIds = [];
        $matchedLedgerIds = [];

        $matches = collect();

        foreach ($matched as $match) {
            $percent = ceil($match->cosine_similarity * 100);
            $newStatement = (new TransactionResource($this->statementRepository->findById($match->statement_id)))->toArray(request());
            $newLedger = (new TransactionResource($this->ledgerRepository->findById($match->ledger_id)))->toArray(request());

            $matches->push([
                'statement' => $newStatement,
                'ledger' => $newLedger,
                'score' => $percent,
                'matched_by' => 'AI'
            ]);

            $matchedLedgerIds[] = $match->ledger_id;
            $matchedStatementIds[] = $match->statement_id;
        }
        //Log::info('Matched Statements', ['data' => $matchedStatementIds]);
        //Log::info('Matched Ledgers', ['data' => $matchedLedgerIds]);

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
            'matches' => $matches->toArray(),
            'unmatched_ledgers' => $unmatchedLedgers,
            'unmatched_statements' => $unmatchedStatements,
            'summary' => [
                'totalMatched' => count($matches),
                'totalUnmatched' => count($unmatchedLedgers) + count($unmatchedStatements),
                'total' => count($unmatchedLedgers) + count($unmatchedStatements) + count($matches)
            ]
        ];
    }

    public function store(array $data)
    {
        $reconciliation = $this->mainRepository->store([
            'user_id' => $data['user_id'],
            'title' => $data['title'],
            'status' => 'draft',
        ]);

        return [
            'status_code' => 200,
            'status' => 'success',
            'message' => "Reconciliation created successfully!",
            'data' => $reconciliation
        ];
    }

    public function addLedgersToRecon(Reconciliation $reconciliation, array $data)
    {
        $reconciliation->ledgers()->sync($data['ledgers']);

        return [
            'status_code' => 200,
            'status' => 'success',
            'message' => "Reconciliation updated successfully!",
            'data' => [
                ...$reconciliation->toArray(),
                'ledgers' => $reconciliation->ledgers->map(function ($ledger) {
                    return [
                        'id' => $ledger->id,
                        'name' => $ledger->name,
                        'type' => $ledger->type,
                        'description' => $ledger->description
                    ];
                })->toArray(),
            ]
        ];
    }

    public function addStatementsToRecon(Reconciliation $reconciliation, array $data)
    {
        $statementFileIds = [];

        foreach ($data['statements'] as $statementData) {
            $savedFile = $this->fileRepository->store([
                'user_id' => $data['user_id'],
                'file_name' => $statementData['name'],
                'file_path' => $statementData['path'],
                'type' => 'Statement',
            ]);

            Log::info('Saved File: ', ['file' => $savedFile]);

            $statementFile = $this->statementFileRepository->store([
                'user_file_id' => $savedFile->id,
                ...collect($statementData)->except([
                    'period'
                ])->toArray(),
                ...$statementData['period']
            ]);

            $structuredStatements = $this->mappingStatement($statementData, $statementData['mapper']);
            $this->savingStatements($structuredStatements, $reconciliation);

            $statementFileIds[] = $statementFile->id;
        }

        $reconciliation->statementFiles()->attach($statementFileIds);


        $this->mainRepository->updateRecon($reconciliation, [
            ...$reconciliation->toArray(),
            'step' => 3,
            'status' => 'draft'
        ]);

        return [
            'status_code' => 200,
            'status' => 'success',
            'message' => "Statements added successfully!",
            'data' => [
                ...$reconciliation->toArray(),
                'statements' => $reconciliation->statementFiles->map(function ($statement) {
                    return [
                        'id' => $statement->id,
                        'name' => $statement->userFile->file_name,
                        'period' => [
                            'from' => $statement->start_date,
                            'to' => $statement->end_date,
                        ],
                        'bank_account' => $statement->bankAccount->bank_name,
                    ];
                })->toArray(),
            ]
        ];
    }


    public function usingEmbeddings(array $statements, array $ledgers, User $user, Reconciliation $reconciliation)
    {
        $startTime = microtime(true);
        try {
            event(new ReconciliationProgressUpdated($reconciliation->id, 'Structuring bank statements...'));
            foreach ($statements as $statement) {
                $structuredStatements = $this->mappingStatement($statement, $statement['mapper']);
            }
            $this->mainRepository->updateRecon($reconciliation, [
                ...$reconciliation->toArray(),
                'step' => 2
            ]);

            event(new ReconciliationProgressUpdated($reconciliation->id, 'Saving bank statements...'));
            $this->savingStatements($structuredStatements, $reconciliation);
            $this->mainRepository->updateRecon($reconciliation, [
                ...$reconciliation->toArray(),
                'step' => 3
            ]);

            event(new ReconciliationProgressUpdated($reconciliation->id, 'Fetching ledger entries...'));
            $savedStatements = $this->statementRepository->findAll($reconciliation);
            $ledgers = $reconciliation->ledgers()->pluck('bookkeeping_ledger_id')->toArray();
            $savedLedgers = $this->ledgerRepository->findAllByType($ledgers);
            $this->mainRepository->updateRecon($reconciliation, [
                ...$reconciliation->toArray(),
                'step' => 4
            ]);

            event(new ReconciliationProgressUpdated($reconciliation->id, 'Preparation for AI matching...'));
            $this->generateStatementEmbeddings($savedStatements);
            $this->generateLedgerEmbeddings($savedLedgers);
            $this->mainRepository->updateRecon($reconciliation, [
                ...$reconciliation->toArray(),
                'step' => 5
            ]);

            event(new ReconciliationProgressUpdated($reconciliation->id, 'AI matching in progress...'));
            $response = $this->matchUsingEmbeddings($savedStatements, $savedLedgers);
            $this->mainRepository->updateRecon($reconciliation, [
                ...$reconciliation->toArray(),
                'step' => 6
            ]);

            event(new ReconciliationProgressUpdated($reconciliation->id, 'Compiling response...'));
            $this->mainRepository->storeResponse([
                'reconciliation_id' => $reconciliation->id,
                'data' => $response
            ]);
            $this->mainRepository->updateRecon($reconciliation, [
                ...$reconciliation->toArray(),
                'step' => 7
            ]);

            Mail::to($user->email)->send(new ReconciliationCompleted($reconciliation, $user));
            event(new ReconciliationProgressUpdated($reconciliation->id, 'Reconciliation completed successfully!'));
            $duration = round(microtime(true) - $startTime, 2);
            $this->mainRepository->updateRecon($reconciliation, [
                ...$reconciliation->toArray(),
                'duration' => $duration,
                'status' => 'completed',
            ]);
            Log::info("Reconciliation completed in {$duration} seconds");

            Log::info('Reconciliation: ', ['status' => 'done!']);

            return [
                'reconciliation_id' => $reconciliation->id,
                ...$response
            ];
        } catch (\Exception $e) {
            Log::error('Failed to run job', ['error' =>  $e]);
            event(new ReconciliationProgressUpdated($reconciliation->id, 'Reconciliation failed. Please try again!'));
            throw $e;
        }
    }

    public function saveDraft(Reconciliation $reconciliation)
    {
        $this->mainRepository->updateRecon($reconciliation, [
            ...$reconciliation->toArray(),
            'status' => 'draft'
        ]);
    }

    public function export(Reconciliation $reconciliation)
    {
        $record = $this->fetchResults($reconciliation);

        $file = $this->generateCSV($record);

        return Response::download($file)->deleteFileAfterSend(true);
    }

    protected function generateCSV($data)
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $exportFileName = public_path("reconciled-data-" . now()->format('Y-m-d_H-i-s') . ".csv");

        $exportFile = fopen($exportFileName, 'w');
        fputcsv($exportFile, ['Bank Statement', '', '', '', '', 'Ledger', '', '']);
        fputcsv($exportFile, ['Date', 'Description', 'Amount', 'Status', 'Score', 'Date', 'Description', 'Amount']);

        foreach ($data['matches'] as $match) {
            $arr = [
                $match['statement']['Date'],
                $match['statement']['Description'],
                $match['statement']['Amount'],
                'Matched',
                $match['matched_by'],
                $match['score'],
                $match['ledger']['Date'],
                $match['ledger']['Description'],
                $match['ledger']['Amount'],
            ];
        }

        foreach ($data['unmatched_statements'] as $row) {
            fputcsv($exportFile, [$row['Date'], $row['Description'], $row['Amount'], 'Unmatched']);
        }

        foreach ($data['unmatched_ledgers'] as $row) {
            $updated = ['', '', '', 'Unmatched', '', $row['Date'], $row['Description'], $row['Amount']];
            fputcsv($exportFile, $updated);
        }

        fclose($exportFile);

        return $exportFileName;
    }

    public function fetchResults(Reconciliation $reconciliation)
    {
        $savedStatements = $this->statementRepository->findAll($reconciliation);
        $ledgerTypes = $reconciliation->ledgers->pluck('id')->toArray();
        $savedLedgers = $this->ledgerRepository->findAllByType($ledgerTypes);

        $matched = $this->matchedRepository->getMatches($reconciliation->id);

        $matchedStatementIds = [];
        $matchedLedgerIds = [];

        $matches = collect();

        foreach ($matched as $match) {
            $percent = $match->score;
            $newStatement = (new TransactionResource($this->statementRepository->findById($match->statement_id)))->toArray(request());
            $newLedger = (new TransactionResource($this->ledgerRepository->findById($match->ledger_id)))->toArray(request());

            $matches->push([
                'statement' => $newStatement,
                'ledger' => $newLedger,
                'score' => $percent,
                'matched_by' => 'AI'
            ]);

            $matchedLedgerIds[] = $match->ledger_id;
            $matchedStatementIds[] = $match->statement_id;
        }

        //Log::info('Matched Statements', ['data' => $matchedStatementIds]);
        //Log::info('Matched Ledgers', ['data' => $matchedLedgerIds]);
        //Log::info('Matches', ['data' => $matches]);

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
                'totalUnmatched' => count($unmatchedStatements),
                'total' => count($unmatchedLedgers) + count($unmatchedStatements) + count($matches),
                'manual_matched' => count($matches->filter(fn($match) => $match['matched_by'] === 'manual')),
                'ai_matched' => count($matches->filter(fn($match) => $match['matched_by'] === 'AI')),
                'duration' => $reconciliation->duration,
                'status' => $reconciliation->status,
                'updated_at' => $reconciliation->updated_at,
            ]
        ];;
    }

    public function matchUnmatch(Reconciliation $reconciliation, array $matches)
    {
        foreach ($matches as $match) {
            if ($match['action'] == 'match') {
                $this->matchedRepository->storeByIds($match['ledger'], $match['statement'], $match['score'], $match['matched_by']);
            } else if ($match['action'] == 'unmatch') {
                $this->matchedRepository->removeByIds($match['ledger'], $match['statement']);
            }
        }

        return $this->fetchResults($reconciliation);
    }

    public function fetchUserReconciliations(User $user)
    {
        try {
            $reconciliations = $this->mainRepository->list($user);
            $statements = [];
            foreach ($reconciliations as $recon) {
                $reconciliation = $this->mainRepository->get($recon->id);
                $statements[] = $this->statementRepository->findAll($reconciliation);
            }

            return [
                'status_code' => 200,
                'status' => 'success',
                'message' => "User's reconciliations fetched successfuly!",
                'data' => [
                    'reconciliations' => $reconciliations,
                    'summary' => [
                        'total' => count($reconciliations),
                        'completed' => collect($reconciliations)->filter(fn($recon) => $recon->status == 'completed')->count(),
                        'pending' => collect($reconciliations)->filter(fn($recon) => $recon->status == 'in-progress')->count(),
                        'total_transactions' => collect($statements)->sum(fn($stmt) => $stmt->amount)
                    ]
                ]
            ];
        } catch (\Exception $e) {
            return response()->json([
                "message" => "Failed to fetch reconciliations",
                "status" => "error",
                "status_code" => 500,
                'data' => [
                    'error' => $e->getMessage()
                ]
            ], 500);
        }
    }

    public function uploadLedger(array $data)
    {
        try {
            $structured = $this->mappingData([$data['ledger_file']], $data['mapper']);
            $formatted = collect($structured)->map(function ($ledg) use ($data) {
                return [
                    ...$ledg,
                    'Transaction Type' => $data['transaction_type'],
                    'bookkeeping_ledger_id' => $data['ledger']
                ];
            });
            $this->savingLedgers($formatted->toArray());

            return response()->json([
                "message" => "Ledger successfully uploaded and saved!",
                'status' => 'success',
                'status_code' => 200,
                'data' => []
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "message" => "Failed to fetch reconciliations",
                "status" => "error",
                "status_code" => 500,
                'data' => [
                    'error' => $e->getMessage()
                ]
            ], 500);
        }
    }

    public function fetchReconResult(Reconciliation $reconciliation)
    {
        $record = ReconciledRecord::where('reconciliation_id', '=', $reconciliation->id)->first();

        if (!$record) {
            return response()->json([
                "message" => "Reconciliation's result doesn't exist",
                "status" => "error",
                "status_code" => 404,
                'data' => []
            ], 404);
        }

        return response()->json([
            "message" => "Successfully fetched reconciliation\'s results",
            "status" => "success",
            "status_code" => 200,
            "data" => $record->data
        ]);
    }

    public function fetchDetails(Reconciliation $reconciliation)
    {
        return response()->json([
            "message" => "Successfully fetched reconciliation\'s summary",
            "status" => "success",
            "status_code" => 200,
            "data" => [
                'last_updated' => $reconciliation->updated_at,
                'title' => $reconciliation->title,
                'bank_accounts' => $reconciliation->statementFiles->map(function ($statement) {
                    return $statement->bankAccount;
                }),
                'ledgers' => $reconciliation->ledgers,
                'status' => $reconciliation->status,
                'duration' => $reconciliation->duration,
                'step' => $reconciliation->step,
            ]
        ]);
    }
}
