<?php
namespace App\Services;

use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use OpenAI;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use App\Repositories\Reconciliation\ReconciliationRepository;
use App\Repositories\UserFile\UserFileRepository;
use App\Repositories\Ledger\LedgerRepository;
use App\Repositories\Statement\StatementRepository;
use App\Repositories\MatchingTransaction\MatchingTransactionRepository;
use App\Models\Reconciliation;
use App\Http\Resources\TransactionResource;

class ReconciliationService
{
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

    public function reconcileWithRecox(string $file1Path, string $file2Path)
    {
        $bankData = $this->loadAndNormalizeFile($file1Path, 'bank_statement');
        $ledgerData = $this->loadAndNormalizeFile($file2Path, 'company_ledger');

        $matches = [];
        $onlyInBank = $bankData;
        $onlyInLedger = $ledgerData;

        foreach ($bankData as $bKey => $bankEntry) {
            foreach ($ledgerData as $lKey => $ledgerEntry) {
                if ($this->isExactMatch($bankEntry, $ledgerEntry)) {
                    $matches[] = [
                        'file1_transaction' => $bankEntry,
                        'file2_transaction' => $ledgerEntry,
                        'match_score' => 100,
                    ];
                    unset($onlyInBank[$bKey]);
                    unset($onlyInLedger[$lKey]);
                    break;
                }
            }
        }

        $onlyInBank = array_values($onlyInBank);
        $onlyInLedger = array_values($onlyInLedger);
        foreach ($onlyInBank as $bKey => $bankEntry) {
            foreach ($onlyInLedger as $lKey => $ledgerEntry) {
                $score = $this->calculateFuzzyMatchScore($bankEntry, $ledgerEntry);
                if ($score > 70) {
                    $matches[] = [
                        'file1_transaction' => $bankEntry,
                        'file2_transaction' => $ledgerEntry,
                        'match_score' => $score,
                    ];
                    unset($onlyInBank[$bKey]);
                    unset($onlyInLedger[$lKey]);
                    break;
                }
            }
        }

        $onlyInBank = array_values($onlyInBank);
        $onlyInLedger = array_values($onlyInLedger);

        return [
            'matches' => $matches,
            'matches_count' => count($matches),
            'only_in_file1' => $onlyInBank,
            'only_in_file2' => $onlyInLedger,
            'unmatched' => [
                'unmatched_file1' => $onlyInBank,
                'unmatched_file2' => $onlyInLedger,
            ],
            'matchSummary' => [
                'totalMatched' => count($matches),
                'totalUnmatchedFile1' => count($onlyInBank),
                'totalUnmatchedFile2' => count($onlyInLedger),
                'totalUnmatched' => count($onlyInBank) + count($onlyInLedger),
            ],
        ];
    }

    protected function loadAndNormalizeFile(string $filePath, string $fileType): array
    {
        $rawData = $this->loadCsv($filePath);
        $normalized = [];

        $headerMapping = [
            'date' => [
                'date', 
                'transaction date', 
                'payment date', 
                'completed date', 
                'Date', 
                'transaction_date', 
                'payment_date', 
                'settlement date', 
                'date of transaction', 
                'date of payment', 
                'entry date', 
                'recorded date', 
                'timestamp', 
                'due date', 
                'payment_due_date'
            ],
            'description' => [
                'description', 
                'details', 
                'remark', 
                'name', 
                'transaction description', 
                'transaction_details', 
                'description of transaction', 
                'note', 
                'memo', 
                'transaction_name', 
                'description_text', 
                'details of transaction', 
                'fee type', 
                'item', 
                'product', 
                'service', 
                'tuition fee', 
                'school fee', 
                'sales description',
                'student ID', 
                'student_id', 
                'ID', 
                'identifier', 
                'customer ID', 
                'customer_id', 
                'invoice number', 
                'receipt number', 
                'transaction ID', 
                'transaction_id',
                'payer name', 
                'full name', 
                'name of student', 
                'name of customer', 
                'parent name',
                'status', 
                'payment status', 
                'transaction status', 
                'fee status', 
                'sales status', 
                'completed', 
                'pending', 
                'failed', 
                'successful'
            ],
            'amount' => [
                'amount', 
                'transaction amount', 
                'total', 
                'cashflow', 
                'inflow', 
                'credit', 
                'debit', 
                'Credit (Inflow)', 
                'Debit (Outflow)', 
                'amount_due', 
                'amount_paid', 
                'payment amount', 
                'total_amount',
                'total amount', 
                'net amount', 
                'gross amount', 
                'value', 
                'price', 
                'fee amount', 
                'sales amount', 
                'tuition amount', 
                'total sales'
            ]
        ];

        $headers = array_keys($rawData[0]);
        $mappedHeaders = $this->detectColumns($headers, $headerMapping);

        foreach ($rawData as $row) {
            $amount = 0;

            if (isset($row[$mappedHeaders['amount']])) {
                $amount = floatval($row[$mappedHeaders['amount']]);
            }

            $normalized[] = [
                'date' => isset($row[$mappedHeaders['date']]) ? $row[$mappedHeaders['date']] : null,
                'description' => isset($row[$mappedHeaders['description']]) ? trim($row[$mappedHeaders['description']]) : null,
                'amount' => $amount,
            ];
        }

        return $normalized;
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

    protected function detectColumns(array $headers, array $headerMapping): array
    {
        $mappedHeaders = [];

        foreach ($headerMapping as $standardField => $possibleHeaders) {
            foreach ($possibleHeaders as $expected) {
                foreach ($headers as $header) {
                    if (stripos($header, $expected) !== false) {
                        $mappedHeaders[$standardField] = $header;
                        break 2; 
                    }
                }
            }
        }

        return [
            'date' => $mappedHeaders['date'] ?? null,
            'description' => $mappedHeaders['description'] ?? null,
            'amount' => $mappedHeaders['amount'] ?? null,
        ];
    }

    protected function findBestColumn(array $headers, array $expectedNames)
    {
        foreach ($expectedNames as $expected) {
            foreach ($headers as $header) {
                if (stripos($header, $expected) !== false) {
                    return $header;
                }
            }
        }
        return null;
    }

    protected function extractNameFromDescription(string $description): string
    {
        if (preg_match('/[A-Z][a-z]+\s[A-Z][a-z]+/', $description, $matches)) {
            return trim($matches[0]);
        }
        return $description;
    }

    protected function normalizeName(string $name): string
    {
        $name = strtolower(trim(str_replace(',', '', $name)));

        $parts = explode(' ', $name);
        if (count($parts) > 1) {
            return implode(' ', array_reverse($parts));
        }
        return $name;
    }

    protected function calculateNameSimilarity($name1, $name2)
    {
        $name1 = $this->normalizeName($name1);
        $name2 = $this->normalizeName($name2);

        if (str_contains($name1, $name2) || str_contains($name2, $name1)) {
            return 100;
        }

        if ($name1 === implode(' ', array_reverse(explode(' ', $name2)))) {
            return 95;
        }

        similar_text($name1, $name2, $percent);
        return ($percent >= 75) ? $percent : 0;
    }

    protected function amountsAreClose($amount1, $amount2, $tolerance = 500)
    {
        return abs($amount1 - $amount2) <= $tolerance;
    }

    protected function datesAreClose($date1, $date2, $tolerance = 2)
    {
        if (!$date1 || !$date2) {
            return false;
        }
        $date1 = strtotime($date1);
        $date2 = strtotime($date2);
        return abs($date1 - $date2) <= ($tolerance * 86400);
    }

    protected function loadFile(string $filePath): array
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        if ($extension === 'csv') {
            return $this->loadCsv($filePath);
        } elseif (in_array($extension, ['xls', 'xlsx'])) {
            return $this->loadExcel($filePath);
        }

        throw new \Exception("Unsupported file format.");
    }

    protected function loadCsv(string $filePath): array
    {
        $data = [];
        if (($handle = fopen($filePath, 'r')) !== false) {
            $headers = fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== false) {
                $data[] = array_combine($headers, $row);
            }
            fclose($handle);
        }
        return $data;
    }

    protected function loadExcel(string $filePath): array
    {
        $array = Excel::toArray([], $filePath)[0];
        if (empty($array)) {
            throw new \Exception("Empty Excel file.");
        }
        $headers = array_shift($array);
        return array_map(fn ($row) => array_combine($headers, $row), $array);
    }

    protected function generatePrompt(array $data1, array $data2): string
    {
        $exampleFile1 = [
            ["name" => "John Doe", "amount" => 1000, "date" => "2024-01-10"],
            ["name" => "Jane Smith", "amount" => 2000, "date" => "2024-01-12"],
            ["name" => "Alice Brown", "amount" => 1500, "date" => "2024-01-15"]
        ];

        $exampleFile2 = [
            ["name" => "Doe John", "amount" => 1000, "date" => "2024-01-10"],
            ["name" => "Jane Smith", "amount" => 2000, "date" => "2024-01-12"],
            ["name" => "Bob Martin", "amount" => 3000, "date" => "2024-01-18"]
        ];

        $exampleOutput = [
            "matches" => [
                [
                    "file1_transaction" => ["name" => "John Doe", "amount" => 1000, "date" => "2024-01-10"],
                    "file2_transaction" => ["name" => "Doe John", "amount" => 1000, "date" => "2024-01-10"],
                    "match_score" => 95
                ],
                [
                    "file1_transaction" => ["name" => "Jane Smith", "amount" => 2000, "date" => "2024-01-12"],
                    "file2_transaction" => ["name" => "Jane Smith", "amount" => 2000, "date" => "2024-01-12"],
                    "match_score" => 100
                ]
            ],
            "only_in_file1" => [
                ["name" => "Alice Brown", "amount" => 1500, "date" => "2024-01-15"]
            ],
            "only_in_file2" => [
                ["name" => "Bob Martin", "amount" => 3000, "date" => "2024-01-18"]
            ],
            "unmatched" => [
                "unmatched_file1" => [
                    ["name" => "Alice Brown", "amount" => 1500, "date" => "2024-01-15"]
                ],
                "unmatched_file2" => [
                    ["name" => "Mark Wilson", "amount" => 1800, "date" => "2024-01-16"]
                ]
            ],
            "matchSummary" => [
                "totalMatched" => 2,
                "totalUnmatched" => 2
            ]
        ];

        $formattedData1 = json_encode($data1);
        $formattedData2 = json_encode($data2);

        return "You are a transaction reconciliation assistant. Match transactions from File1 and File2 based on names, amounts, and dates.

        ## Reconciliation Rules
        - Names may have **minor spelling differences** (e.g., 'Mark Essien' and 'Mark Essin' should be treated as the same person).
        - Amounts can match exactly or be within a reasonable range that may cover for charges, taxes, VAT (e.g., 40,000 and 40,600 should be considered a match).
        - **Dates are not a factor** in reconciliation and should be ignored for example a bank statement of 13/6/24 can match with a company ledger of 17/7/24 if other columns such as description and amount are a match .
        - Ignore any special **symbols or formatting differences** in the name fields, for example (Peter's should match with Peter or Mitchell@ should match with Mitchell,.
        - Some names may match based on nicknames for example, Sammy Song and Samuel Song, Goodness Samuel and Goody Samuel, Goddy Akpabio and Godswill Akpabio can match.
        - **Case sensitivity** should be ignored in the name fields.
        - Synonyms of the headers description
        - Ignore unrelated columns unless the value is of the columns are valuable to the decision making of the reconciliation.

        Here is an example of how the reconciliation should work:

        Example File1 Transactions: " . json_encode($exampleFile1) . "
        Example File2 Transactions: " . json_encode($exampleFile2) . "

        Expected Output Format:
        " . json_encode($exampleOutput) . "

        Now, reconcile the following real transactions:

        File1 Transactions: {$formattedData1}
        File2 Transactions: {$formattedData2}

        Return a JSON with 'matches', 'only_in_file1', 'only_in_file2', 'unmatched', and 'matchSummary'.";
    }

    protected function processAIResponse(string $response, array $data1, array $data2)
    {
        $cleanResponse = trim(str_replace(["```json", "```"], "", $response));

        $decodedResponse = json_decode($cleanResponse, true);

        $unmatched = $decodedResponse['unmatched'] ?? [];
        $unmatchedFile1 = $unmatched['unmatched_file1'] ?? [];
        $unmatchedFile2 = $unmatched['unmatched_file2'] ?? [];

        return [
            'matches' => $decodedResponse['matches'] ?? [],
            'only_in_file1' => $decodedResponse['only_in_file1'] ?? $data1,
            'only_in_file2' => $decodedResponse['only_in_file2'] ?? $data2,
            'unmatched' => [
                'unmatched_file1' => $unmatchedFile1,
                'unmatched_file2' => $unmatchedFile2,
            ],
            'matchSummary' => $decodedResponse['matchSummary'] ?? [
                'totalMatched' => count($decodedResponse['matches'] ?? []),
                'totalUnmatchedFile1' => count($unmatchedFile1),
                'totalUnmatchedFile2' => count($unmatchedFile2),
                'totalUnmatched' => count($unmatchedFile1) + count($unmatchedFile2)
            ]
        ];
    }

    public function reconcileWithOpenAI(string $file1Path, string $file2Path)
    {
        $data1 = $this->loadFile($file1Path);
        $data2 = $this->loadFile($file2Path);

        $prompt = $this->generatePrompt($data1, $data2);
        $response = $this->callOpenAI($prompt);

        return $this->processAIResponse($response, $data1, $data2);
    }

    protected function callOpenAI(string $prompt)
    {
        $client = OpenAI::client(env('OPENAI_API_KEY'));

        $response = $client->chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a transaction reconciliation assistant.'],
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        return $response['choices'][0]['message']['content'];
    }

    public function reconcileWithDeepSeek(string $file1Path, string $file2Path)
    {
        $data1 = $this->loadFile($file1Path);
        $data2 = $this->loadFile($file2Path);

        $prompt = $this->generatePrompt($data1, $data2);
        $response = $this->callDeepSeek($prompt);

        return $this->processAIResponse($response, $data1, $data2);
    }

    protected function callDeepSeek(string $prompt)
    {
        $client = new \GuzzleHttp\Client();
        $apiKey = env('DEEPSEEK_API_KEY');

        $response = $client->post('https://api.deepseek.com/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'deepseek-chat',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a transaction reconciliation assistant.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'stream' => false,
            ],
        ]);

        $body = json_decode($response->getBody()->getContents(), true);

        Log::info('DeepSeek response:', ['response' => $body]);

        return $body['choices'][0]['message']['content'] ?? '';
    }

    public function reconcileWithGemini(string $file1Path, string $file2Path)
    {
        $data1 = $this->loadFile($file1Path);
        $data2 = $this->loadFile($file2Path);

        $prompt = $this->generatePrompt($data1, $data2);
        $response = $this->callGemini($prompt);

        return $this->processAIResponse($response, $data1, $data2);
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

    public function generateExport(array $data){
        $timestamp = now()->format('Y-m-d_H-i-s');
        $exportFileName = public_path("reconciled-data-" . now()->format('Y-m-d_H-i-s') . ".csv");

        $exportFile = fopen($exportFileName, 'w');
        fputcsv($exportFile, ['Bank Statement', '', '', '', 'Ledger']);
        fputcsv($exportFile, ['Date', 'Description', 'Amount', 'Status', 'Date', 'Description', 'Amount']);

        foreach ($data['matches'] as $row) {
            $rowData = [
                ...$row['file1_transaction'],
                $row['status']
            ];
            foreach ($row['file2_transaction'] as $value) {
                array_push($rowData, $value);
            }
            fputcsv($exportFile, $rowData);
        }

        foreach ($data['unmatched']['unmatched_file1'] as $row) {
            fputcsv($exportFile, [...$row, 'Unmatched']);
        }

        fclose($exportFile);

        return Response::download($exportFileName)->deleteFileAfterSend(true);
    }

     public function store(array $data): Reconciliation
     {
        $statement = $this->fileRepository->store([
            'user_id' => $data['user'],
            'file_name' => $data['statement'],
            'type' => 'Bank Statement'
        ]);

        $ledger = $this->fileRepository->store([
            'user_id' => $data['user'],
            'file_name' => $data['ledger'],
            'type' => 'Ledger'
        ]);

        $reconciliation = $this->mainRepository->store([
            'user_id' => $data['user'],
            'option' => $data['ai']
        ]);

        $files = $reconciliation->files()->attach([[
            'id' => Str::uuid(),
            'file_id' => $statement->id
        ], [
            'id' => Str::uuid(),
            'file_id' => $ledger->id
        ]
        ]);

        $record = $this->mainRepository->storeResponse([
            'reconciliation_id' => $reconciliation->id,
            'data' => $data['response']
        ]);

        return $reconciliation;
     }

    public function matchUnmatch(array $data, Reconciliation $reconciliation){
        $ledger = $this->ledgerRepository->store([
            'reconciliation_id' => $reconciliation->id,
            ...$data['ledger']
        ]);

        $statement = $this->statementRepository->store([
            'reconciliation_id' => $reconciliation->id,
            ...$data['statement']
        ]);

        $filteredStatement = (new TransactionResource($statement))->toArray(request());
        $filteredLedger = (new TransactionResource($ledger))->toArray(request());

        $response = $this->mainRepository->findResponse($reconciliation);
        $resArray = $response->data;
        $newMatch = $this->matchedRepository->store($ledger, $statement);
        $res = [
            'file1_transaction' => $filteredStatement,
            'file2_transaction' => $filteredLedger,
            'match_score' => 100
        ];

        if($data['action'] == 'match'){

            if (($key = array_search($ledger, $resArray['only_in_file2'])) !== false) {
                unset($resArray['only_in_file2'][$key]);
            }else if (($key = array_search($ledger, $resArray['unmatched']['unmatched_file2'])) !== false) {
                unset($resArray['unmatched']['unmatched_file2'][$key]);
            }else if (($key = array_search($statement, $resArray['only_in_file1'])) !== false) {
                $resArray['matchSummary']['totalUnmatched'] -= 1;
                unset($response['only_in_file1'][$key]);
            }else if (($key = array_search($statement, $resArray['unmatched']['unmatched_file1'])) !== false) {
                $resArray['matchSummary']['totalUnmatched'] -= 1;
                unset($resArray['unmatched']['unmatched_file1'][$key]);
            }

            array_push($resArray['matches'], $res);
            $resArray['matchSummary']['totalMatched'] += 2;
        }else if($data['action'] === 'unmatch'){
            $match = $this->matchedRepository->remove($ledger, $statement);

            if (($key = array_search($res, $resArray['matches'])) !== false) {
                $resArray['matchSummary']['totalMatched'] -= 2;
                unset($resArray['matches'][$key]);
            }

            array_push($resArray['only_in_file1'], $filteredStatement);
            array_push($resArray['unmatched']['unmatched_file1'], $filteredStatement);
            array_push($resArray['only_in_file2'], $filteredLedger);
            array_push($resArray['unmatched']['unmatched_file2'], $filteredLedger);
            $resArray['matchSummary']['totalUnmatched'] += 2;
        }

        $response->data = $resArray;
        $updated = $this->mainRepository->updateResponse($reconciliation, $response->data);

        return [
            'status' => 'success',
            'status_code' => 200,
            'message' => 'Successfully updated the reconciliation!',
            'data' => [
                'reconciliation_id' => $reconciliation->id,
                ...$updated->data
            ]
        ];
    }
}
