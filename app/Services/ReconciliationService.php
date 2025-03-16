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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

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

            $resArray['only_in_file2'] = array_values(array_filter($resArray['only_in_file2'], function ($item) use ($filteredLedger) {
                return !(
                    json_encode($item) === json_encode($filteredLedger)
                );
            }));

            $resArray['unmatched']['unmatched_file2'] = array_values(array_filter($resArray['unmatched']['unmatched_file2'], function ($item) use ($filteredLedger) {
                return !(
                    json_encode($item) === json_encode($filteredLedger)
                );
            }));

            $resArray['only_in_file1'] = array_values(array_filter($resArray['only_in_file1'], function ($item) use ($filteredStatement) {
                return !(
                    json_encode($item) === json_encode($filteredStatement)
                );
            }));

            $resArray['unmatched']['unmatched_file1'] = array_values(array_filter($resArray['unmatched']['unmatched_file1'], function ($item) use ($filteredStatement) {
                    return !(
                        json_encode($item) === json_encode($filteredStatement)
                    );
                }));
            array_push($resArray['matches'], $res);

            $resArray['matchSummary']['totalUnmatched'] = count($resArray['unmatched']['unmatched_file1']) + count($resArray['unmatched']['unmatched_file2']);
            $resArray['matchSummary']['totalMatched'] = count($resArray['matches']);

        }else if($data['action'] === 'unmatch'){
            $match = $this->matchedRepository->remove($ledger, $statement);

            $resArray['matches'] = array_values(array_filter($resArray['matches'], function ($item) use ($filteredStatement, $filteredLedger) {
            return !(
                json_encode($item['file1_transaction']) === json_encode($filteredStatement) &&
                json_encode($item['file2_transaction']) === json_encode($filteredLedger)
            );
        }));

            array_push($resArray['only_in_file1'], $filteredStatement);
            array_push($resArray['unmatched']['unmatched_file1'], $filteredStatement);
            array_push($resArray['only_in_file2'], $filteredLedger);
            array_push($resArray['unmatched']['unmatched_file2'], $filteredLedger);


            $resArray['matchSummary']['totalUnmatched'] = count($resArray['unmatched']['unmatched_file1']) + count($resArray['unmatched']['unmatched_file2']);
            $resArray['matchSummary']['totalMatched'] = count($resArray['matches']);
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

    function loadComplexCsv($filePath)
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

    public function storeReconciliation($file1, $file2){
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

    public function structuringData($file){
        $data = $this->loadComplexCsv($file);

        $chunks = array_chunk($data, 15);

        $structured = [];

        $prompt = "Please structure the attached JSON object into a JSON object with the following properties: Date, Person, Amount and Other Information. The JSON object could be a school ledger, an invoice ledger, a company ledger, a hospital ledger or a bank statement. Please keep this in mind as you go through the dataset. The person property can be derived from properties like Student Name, Student ID, Invoice ID, Invoice Detail, Narration, Summary, Remarks or any other synonyms that are used in a ledger or bank statement. Please only include the exact value provided in the document only. The amount can be derived from the debit, credit, amount, total, or anything that fits this criteria. Ensure the value for the amount that has been paid only so put into consideration any synonyms that may highlight this. Any other information should be added to the 'Other Information' property. Intelligently map through all the properties in the JSON and extract all the relevant information for this data structure. Use the relevant columns to extract this data and ensure the amount is always an absolute value and it should not have any symbols. Return all the data present in the provided JSON in JSON format. Please don't truncate the result.";

        foreach ($chunks as $chunk) {
            $response = $this->callGemini("$prompt. Here's the JSON you need to structure: " . json_encode($chunk) . ". Please return only a valid JSON object");

            $cleanResponse = str_replace(["\"\"", "```json", "```"], "", $response);

            $decodedResponse = json_decode($cleanResponse, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decodedResponse)) {
                $structured = array_merge($structured, $decodedResponse);
            } else {
                \Log::error('Failed to decode Gemini response for data2: ' . json_last_error_msg());
            }
        }

        return $structured;
    }

    public function savingData(array $statements, array $ledgers, Reconciliation $reconciliation){
        DB::beginTransaction();

        $this->statementRepository->storeMany($statements, $reconciliation);
        $this->ledgerRepository->storeMany($ledgers, $reconciliation);

        DB::commit();
    }

    public function findMatches(Reconciliation $reconciliation){
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
            'reconciliation_id' => $reconciliation->id
            'matches' => $matches,
            'unmatched_statements' => $unmatchedStatements,
            'unmatched_ledgers' => $unmatchedLedgers
        ];
    }

    public function testGemini($statement, $ledger)
    {
        $reconciliation = $this->storeReconciliation($statement, $ledger);

        $structuredStatements = $this->structuringData($statement);
        $structuredLedgers = $this->structuringData($ledger);

        $this->savingData($structuredStatements, $structuredLedgers, $reconciliation);

        return $this->findMatches($reconciliation);
    }
}
