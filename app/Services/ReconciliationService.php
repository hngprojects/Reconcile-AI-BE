<?php
namespace App\Services;

use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use OpenAI;
use Illuminate\Support\Facades\Log;

class ReconciliationService
{
    public function reconcileWithRecox(string $file1Path, string $file2Path)
    {
        $data1 = $this->loadFile($file1Path);
        $data2 = $this->loadFile($file2Path);

        $columns1 = $this->detectColumns($data1);
        $columns2 = $this->detectColumns($data2);

        $matches = [];
        $onlyInFile1 = [];
        $onlyInFile2 = [];

        foreach ($data1 as $row1) {
            $bestMatch = null;
            $highestScore = 0;

            $cleanedName1 = $this->extractNameFromDescription($row1[$columns1['name']]);
            $normalizedName1 = $this->normalizeName($cleanedName1);

            foreach ($data2 as $index => $row2) {
                $normalizedName2 = $this->normalizeName($row2[$columns2['name']]);
                $nameScore = $this->calculateNameSimilarity($normalizedName1, $normalizedName2);

                if ($nameScore > 70) {
                    if ($nameScore > $highestScore) {
                        $highestScore = $nameScore;
                        $bestMatch = $index;
                    }
                }
            }

            if ($bestMatch !== null) {
                $matches[] = [
                    'file1_transaction' => $row1,
                    'file2_transaction' => $data2[$bestMatch],
                    'match_score' => $highestScore
                ];
                unset($data2[$bestMatch]);
            } else {
                $onlyInFile1[] = $row1;
            }
        }

        $onlyInFile2 = array_values($data2);

        return [
            'matches' => $matches,
            'matches_count' => count($matches),
            'only_in_file1' => $onlyInFile1,
            'only_in_file2' => $onlyInFile2,
        ];
    }

    protected function detectColumns(array $data)
    {
        $headers = array_keys($data[0]);

        return [
            'name' => $this->findBestColumn($headers, ['name', 'full name', 'student name', 'description']),
            'amount' => $this->findBestColumn($headers, ['amount', 'transaction amount', 'total']),
            'date' => $this->findBestColumn($headers, ['date', 'transaction date', 'payment date'])
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

    public function reconcileWithOpenAI(string $file1Path, string $file2Path)
    {
        $data1 = $this->loadFile($file1Path);
        $data2 = $this->loadFile($file2Path);

        $prompt = $this->generateOpenAIPrompt($data1, $data2);
        $response = $this->callOpenAI($prompt);

        return $this->processOpenAIResponse($response, $data1, $data2);
    }

    protected function generateOpenAIPrompt(array $data1, array $data2): string
    {
        $formattedData1 = json_encode(array_slice($data1, 0, 5));
        $formattedData2 = json_encode(array_slice($data2, 0, 5));

        return "You are a reconciliation assistant. Match transactions from File1 and File2 based on names, amounts, and dates.
        
        File1 Transactions: {$formattedData1}
        File2 Transactions: {$formattedData2}

        Return a JSON with 'matches', 'only_in_file1', 'only_in_file2'.";
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

    protected function processOpenAIResponse(string $response, array $data1, array $data2)
    {
        $decodedResponse = json_decode($response, true);

        return [
            'matches' => $decodedResponse['matches'] ?? [],
            'only_in_file1' => $decodedResponse['only_in_file1'] ?? $data1,
            'only_in_file2' => $decodedResponse['only_in_file2'] ?? $data2,
        ];
    }

    public function reconcileWithDeepSeek(string $file1Path, string $file2Path)
    {
        $data1 = $this->loadFile($file1Path);
        $data2 = $this->loadFile($file2Path);

        $prompt = $this->generateDeepSeekPrompt($data1, $data2);
        $response = $this->callDeepSeek($prompt);

        return $this->processDeepSeekResponse($response, $data1, $data2);
    }

    protected function generateDeepSeekPrompt(array $data1, array $data2): string
    {
        $formattedData1 = json_encode(array_slice($data1, 0, 5));
        $formattedData2 = json_encode(array_slice($data2, 0, 5));

        return "Hello!";
        /* return "You are a reconciliation assistant. Match transactions from File1 and File2 based on names, amounts, and dates.
        
        File1 Transactions: {$formattedData1}
        File2 Transactions: {$formattedData2}

        Return a JSON with 'matches', 'only_in_file1', 'only_in_file2'."; */
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

    protected function processDeepSeekResponse(array $response, array $data1, array $data2)
    {
        $content = $response['choices'][0]['message']['content'] ?? '{}';
        $decodedResponse = json_decode($content, true);

        return [
            'matches' => $decodedResponse['matches'] ?? [],
            'only_in_file1' => $decodedResponse['only_in_file1'] ?? $data1,
            'only_in_file2' => $decodedResponse['only_in_file2'] ?? $data2,
            'decoded_response' => $decodedResponse,
        ];
    }

    public function reconcileWithGemini(string $file1Path, string $file2Path)
    {
        $data1 = $this->loadFile($file1Path);
        $data2 = $this->loadFile($file2Path);

        $prompt = $this->generateGeminiPrompt($data1, $data2);
        $response = $this->callGemini($prompt);

        return $this->processGeminiResponse($response, $data1, $data2);
    }

    protected function generateGeminiPrompt(array $data1, array $data2): string
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

        $formattedData1 = json_encode(array_slice($data1, 0, 5));
        $formattedData2 = json_encode(array_slice($data2, 0, 5));

        return "You are a transaction reconciliation assistant. Match transactions from File1 and File2 based on names, amounts, and dates.
        
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

    protected function processGeminiResponse(string $response, array $data1, array $data2)
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
}
