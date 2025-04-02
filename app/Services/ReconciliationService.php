<?php
namespace App\Services;

<<<<<<< HEAD
use Gemini\Laravel\Facades\Gemini;
use Gemini\Types\Blob;
use Gemini\Types\MimeType;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Facades\Storage;

class ReconciliationService
{

    public function csvToJson($path)
    {
        if (!file_exists($path)) {
            return "Error: File not found!";
        }

        $rows = array_map('str_getcsv', file($path));
        $header = array_shift($rows);
        $data = array_map(fn($row) => array_combine($header, $row), $rows);

        return json_encode($data, JSON_PRETTY_PRINT);
    }

    public function reconcileFiles($bankStatementPath, $ledgerPath)
=======
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
use App\Models\Statement;
use App\Http\Resources\TransactionResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Support\Collection;

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
            'date' => ['date', 'transaction date', 'payment date', 'completed date', 'Date'],
            'description' => ['description', 'details', 'remark', 'name', 'transaction description'],
            'amount' => ['amount', 'transaction amount', 'total', 'cashflow', 'inflow', 'credit', 'debit', 'Credit (Inflow)', 'Debit (Outflow)']
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
>>>>>>> 6ad9c69c36498c12ca59168b25484bf77bdaaf61
    {
        ini_set('max_execution_time', 300);
        $bankStatementJson = $this->csvToJson($bankStatementPath);
        $ledgerJson = $this->csvToJson($ledgerPath);

<<<<<<< HEAD
        $prompt = "Analyze these JSON strings: \n\nBank Statement JSON: $bankStatementJson \n\nCompany Ledger JSON: $ledgerJson\n\nReconciliation Instructions:\n\n" .
    "1. **Column Mapping:**\n" .
    "   - Bank Statement: Use the column named 'Date' for Date, 'ExtractedName' for Description, and 'Amount' for Amount. IMPORTANT: The Description is the 'ExtractedName' column, not the 'Description' column.Only use those 3 columns in the bank statement\n" .
    "   - Company Ledger: Use the column named 'Student Name' for Description, and 'Amount' for Amount. IMPORTANT: The Description is 'Student Name' , not the 'Description' column . Only use those 2 columns in the ledger statement. There is no date in the ledger.\n" .
    "2. **Matching Logic:**\n" .
    "   - Amount: Amounts must match exactly.\n" .
    "   - Description: Descriptions should be considered a match if at least 70% of the words are similar (case-insensitive). \n" .
    "3. **Bank Statement Only:** Since the ledger statement does not have dates, only the amount and description are needed. The assumption is that the money came in that day to be considered matched\n" .
    "4. **Response Format:**  Return the data in a JSON format that strictly adheres to the structure defined below. Do not include any other text or explanations. I only need the JSON response.\n\n" .
   "```json\n" .
   "   {\n" .
   "     \"reconciliationSummary\": {\n" .
   "       \"totalMatchedTransactions\": 0,\n" .
   "       \"totalUnmatchedTransactions\": 0,\n" .
   "       \"accuracyRate\": 0.0\n" .
   "     },\n" .
   "     \"matchedTransactions\": [\n" .
   "        {\n" .
   "         \"status\": \"Matched\",\n" .
   "         \"bank_statement\": {\n" .
   "           \"date\": \"\",\n" .
   "           \"description\": \"\",\n" .
   "           \"amount\": \"\"\n" .
   "         },\n" .
   "         \"company_ledger\": {\n" .
   "           \"description\": \"\",\n" .
   "           \"date\": \"\",\n" .
   "           \"amount\": \"\"\n" .
   "         }\n" .
   "       },\n" .
   "       ...\n" .
   "     ],\n" .
   "     \"unmatchedTransactions\": [\n" .
   "        {\n" .
   "         \"status\": \"Unmatched\",\n" .
   "         \"bank_statement\": {\n" .
   "           \"date\": \"\",\n" .
   "           \"description\": \"\",\n" .
   "           \"amount\": \"\"\n" .
   "         },\n" .
   "         \"company_ledger\": {\n" .
   "           \"description\": \"\",\n" .
   "           \"date\": \"\",\n" .
   "           \"amount\": \"\"\n" .
   "         }\n" .
   "       },\n" .
   "       ...\n" .
   "     ]\n" .
   "   }\n" .
   "   ```\n\n" .
    "Important Considerations:\n" .
    "   - Ensure all credit transactions from the bank statement are accounted for, either as 'Matched' or 'Unmatched'.\n" .
    "   - If a bank statement transaction has multiple possible matches in the ledger, choose the *best* match based on a combination of amount and description similarity.\n" .
    "   - **Do not truncate the output.**  Return the complete reconciliation result, including *all* unmatched bank statement transactions.\n" .
    "   - The ledger statement has no date. The assumption is that the date does not matter, the amount and description should be matched only\n" .
    "   - Only respond with a response in JSON format following the Response Format provided, nothing more, nothing else, I only need the response.";
=======
        if ($extension === 'csv') {
            return $this->loadCsv($filePath);
        } elseif (in_array($extension, ['xls', 'xlsx'])) {
            return $this->loadExcel($filePath);
        }
>>>>>>> 6ad9c69c36498c12ca59168b25484bf77bdaaf61

        $aiResponse = Gemini::geminiFlash()->generateContent($prompt);

        $jsonString = str_replace('\n', '', preg_replace('/```(?:json)?\s*([\s\S]*?)```/i', '$1', trim($aiResponse->text())));

        $formatted = json_decode($jsonString, true);

        return $formatted;
    }
<<<<<<< HEAD
=======

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
        - If there's a duplicate and it has a match, create one match for them and add the other instances to the duplicates array. Otherwise, classify one of them as unmatched if they are not matched and add the other instances to the duplicate array

        Here is an example of how the reconciliation should work:

        Example File1 Transactions: " . json_encode($exampleFile1) . "
        Example File2 Transactions: " . json_encode($exampleFile2) . "

        Expected Output Format:
        " . json_encode($exampleOutput) . "

        Now, reconcile the following real transactions:

        File1 Transactions: {$formattedData1}
        File2 Transactions: {$formattedData2}

        Return a JSON with 'matches' as an array, 'only_in_file1' as an array, 'only_in_file2' as an array, 'duplicates' as an array, 'unmatched' as an array, and 'matchSummary' as an array.";
    }

    protected function processAIResponse(string $response, array $data1=[], array $data2=[])
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
            'duplicates' => $decodedResponse['duplicates'] ?? [],
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
        // $client = OpenAI::client(env('OPENAI_API_KEY'));
        $client = OpenAI::client(config('services.ai_key.open_api'));

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
        // $apiKey = env('DEEPSEEK_API_KEY');
        $apiKey = config('services.ai_key.deepseek');

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
        // $apiKey = env('GEMINI_API_KEY');
        $apiKey = config('gemini.api_key');

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
                ucfirst($row['status'])
            ];
            foreach ($row['file2_transaction'] as $value) {
                array_push($rowData, $value);
            }
            fputcsv($exportFile, $rowData);
        }

        foreach ($data['unmatched']['unmatched_file1'] as $row) {
            fputcsv($exportFile, [...$row, 'Unmatched']);
        }

        foreach($data['unmatched']['unmatched_file2'] as $row){
            $updated = ['', '', '', 'Unmatched', ...$row];
            fputcsv($exportFile, $updated);
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

    protected function removeUnmatched(string $type, array $res, array $value) {
        $res['unmatched_' . $type] = array_values(array_filter($res['unmatched_' . $type], function ($item) use ($value) {
            return $item !== $value; // Direct array comparison
        }));

        return $res;
    }

    protected function addUnmatched(string $type, array $res, array $value){
        $res['unmatched_' . $type][] = $value;
        return $res;
    }

    protected function addMatched(array $res, array $statement, array $ledger) {
        $found = false;

        foreach ($res['matches'] as &$data) {
            $statements = collect($data['statements']);
            $ledgers = collect($data['ledgers']);

            $matchingStatement = $statements->firstWhere('statement.id', $statement['id']);
            $matchingLedger = $ledgers->firstWhere('ledger.id', $ledger['id']);

            if ($matchingStatement) {
                $data['ledgers'][] = ['ledger' => $ledger, 'score' => '100%'];
                $found = true;
            } elseif ($matchingLedger) {
                $data['statements'][] = ['statement' => $statement, 'score' => '100%'];
                $found = true;
            }
        }

        if (!$found) {
            $res['matches'][] = [
                'statements' => [['statement' => $statement, 'score' => '100%']],
                'ledgers' => [['ledger' => $ledger, 'score' => '100%']]
            ];
        }

        return $res;
    }

    protected function removeMatch(array $res, array $statement, array $ledger) {
        Log::info('Input res:', ['res' => $res]);

        foreach ($res['matches'] as $key => &$match) {
            $statements = collect($match['statements']);
            $ledgers = collect($match['ledgers']);

            // Check if the statement and ledger exist in this match
            $matchingStatement = $statements->firstWhere('statement.id', $statement['id']);
            $matchingLedger = $ledgers->firstWhere('ledger.id', $ledger['id']);

            if ($matchingStatement && $matchingLedger) {
                Log::info('Found matching statement and ledger:', [
                    'statement' => $matchingStatement,
                    'ledger' => $matchingLedger,
                ]);

                // Remove the matching statement and ledger
                $match['statements'] = $statements->filter(function ($item) use ($statement) {
                    return $item['statement']['id'] !== $statement['id'];
                })->values();

                $match['ledgers'] = $ledgers->filter(function ($item) use ($ledger) {
                    return $item['ledger']['id'] !== $ledger['id'];
                })->values();

                Log::info('Updated match:', ['match' => $match]);

                // If no statements or ledgers are left, remove the entire match
                if (empty($match['statements']) || empty($match['ledgers'])) {
                    Log::info('Removing empty match:', ['key' => $key]);
                    unset($res['matches'][$key]);
                }
            }
        }

        // Reindex the matches array after removing items
        $res['matches'] = array_values($res['matches']);

        Log::info('Updated res:', ['res' => $res]);

        return $res;
    }

    protected function matchRecords($ledger, $statement, $action, $resArray, $filteredLedger, $filteredStatement){
        if($action == 'match'){
            $newMatch = $this->matchedRepository->store($ledger, $statement, 100);
            $resArray = $this->addMatched($resArray, $filteredStatement, $filteredLedger);
            $resArray = $this->removeUnmatched('statements', $resArray, $filteredStatement);
            $resArray = $this->removeUnmatched('ledgers', $resArray, $filteredLedger);

        }else if($action == 'unmatch'){
            $newMatch = $this->matchedRepository->remove($ledger, $statement, 100);
            $resArray = $this->removeMatch($resArray, $filteredStatement, $filteredLedger);
            Log::info('Matches: ', $resArray['matches']);
            $resArray = $this->addUnmatched('statements', $resArray, $filteredStatement);
            $resArray = $this->addUnmatched('ledgers', $resArray, $filteredLedger);
        }
        $resArray['summary']['totalUnmatched'] = count($resArray['unmatched_statements']) + count($resArray['unmatched_ledgers']);
        $resArray['summary']['totalMatched'] = count($resArray['matches']);

        return $resArray;
    }

    public function matchUnmatch(array $data, Reconciliation $reconciliation) {
        $ledgers = [];
        $statements = [];

        foreach ($data['ledgers'] as $ledger) {
            $ledgers[] = $this->ledgerRepository->store([ // Fix method call
                'reconciliation_id' => $reconciliation->id,
                ...$ledger
            ]);
        }

        foreach ($data['statements'] as $statement) {
            $statements[] = $this->statementRepository->store([
                'reconciliation_id' => $reconciliation->id,
                ...$statement
            ]);
        }

        $response = $this->mainRepository->findResponse($reconciliation);
        $resArray = $response->data;
        Log::info('Initial resArray:', ['resArray' => $resArray]);

        // Refactor repetitive logic
        if (count($ledgers) > 1 && count($statements) == 1) {
            $filteredStatement = (new TransactionResource($statements[0]))->toArray(request());
            foreach ($ledgers as $ledg) {
                $filteredLedger = (new TransactionResource($ledg))->toArray(request());
                $resArray = $this->matchRecords($ledg, $statements[0], $data['action'], $resArray, $filteredLedger, $filteredStatement);
            }
        } elseif (count($statements) > 1 && count($ledgers) == 1) {
            $filteredLedger = (new TransactionResource($ledgers[0]))->toArray(request());
            foreach ($statements as $stat) {
                $filteredStatement = (new TransactionResource($stat))->toArray(request());
                $resArray = $this->matchRecords($ledgers[0], $stat, $data['action'], $resArray, $filteredLedger, $filteredStatement);
            }
        } else {
            $filteredStatement = (new TransactionResource($statements[0]))->toArray(request());
            $filteredLedger = (new TransactionResource($ledgers[0]))->toArray(request());
            $resArray = $this->matchRecords($ledgers[0], $statements[0], $data['action'], $resArray, $filteredLedger, $filteredStatement);
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
>>>>>>> 6ad9c69c36498c12ca59168b25484bf77bdaaf61
}
