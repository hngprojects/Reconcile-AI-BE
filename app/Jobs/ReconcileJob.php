<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\ReconciliationProject;
use App\Models\StatementRecord;
use App\Models\LedgerRecord;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ReconcileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $project;
    
    public function __construct(ReconciliationProject $project)
    {
        $this->project = $project;
    }

    public function handle()
    {
        try {
            Log::info('ReconcileJob started for project: ' . $this->project->id);
            $this->project->update(['status' => 'processing']);

            $statementStructured = $this->getStructuredCsvFromGemini($this->project->statement_file);
            $ledgerStructured = $this->getStructuredCsvFromGemini($this->project->ledger_file);

            $statementData = $this->parseStructuredCsv($statementStructured);
            $ledgerData = $this->parseStructuredCsv($ledgerStructured);

            $this->storeRecords($statementData, StatementRecord::class);
            $this->storeRecords($ledgerData, LedgerRecord::class);

            $result = $this->reconcileRecords();

            $this->project->update([
                'status' => 'completed',
                'progress' => 100,
                'result' => json_encode($result),
            ]);

            Log::info('Reconciliation results for project: ' . $this->project->id, ['result' => $result]);
            
            Log::info('ReconcileJob completed for project: ' . $this->project->id);
        } catch (\Exception $e) {
            Log::error('ReconcileJob failed', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->project->update(['status' => 'failed']);
            throw $e;
        }
    }

    protected function getStructuredCsvFromGemini($filePath)
    {
        $fullPath = Storage::path($filePath);
        if (!file_exists($fullPath)) {
            Log::error('CSV file not found', ['path' => $fullPath]);
            throw new \Exception('CSV file not found: ' . $fullPath);
        }

        $csvContent = file_get_contents($fullPath);
        $client = new Client();
        $apiKey = env('GEMINI_API_KEY');
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent";

        $prompt = "Given the following CSV content:\n\n$csvContent\n\nReformat it to have exactly these headers: `date,name_of_person,amount,other_information`. Map existing columns to these headers where possible (e.g., 'Date' to 'date', 'Name' or 'Student Name' to 'name_of_person', 'amt' or 'Amount' to 'amount'). For 'name_of_person', extract the full name from the description if not explicitly labeled (e.g., after 'FROM' or before a delimiter like '/'). In both 'name_of_person' and 'other_information', replace commas (,), slashes (/), pipes (|), and colons (:) with spaces to avoid CSV parsing issues (e.g., 'Ubom, Wisdom Ata' becomes 'Ubom Wisdom Ata', 'FIP:PBL/AUGUSTINE DAVID/DGBNK2' becomes 'FIP PBL AUGUSTINE DAVID DGBNK2'). Remove commas from amounts (e.g., '50,000.00' becomes '50000.00'). If 'date' is missing, use '2025-01-01' as a default. Fill missing columns with empty values. Ensure every row has exactly four columns, properly quoted if needed, and return the reformatted CSV as plain text without wrapping it in ```csv or ``` markers.";
        $response = $client->post($endpoint, [
            'json' => [
                'contents' => [
                    ['parts' => [['text' => $prompt]]]
                ],
            ],
            'query' => ['key' => $apiKey],
        ]);

        $result = json_decode($response->getBody(), true);
        $formattedCsv = $result['candidates'][0]['content']['parts'][0]['text'];

        if (defined('PSYSH_DEBUG')) {
            echo "Prompt for $filePath:\n$prompt\n\n";
            echo "Response from Gemini:\n$formattedCsv\n\n";
        }

        return $formattedCsv;
    }

    protected function parseStructuredCsv($csvContent)
    {
        $csvContent = preg_replace('/^```csv\s*|\s*```$/m', '', trim($csvContent));
        $lines = array_filter(explode("\n", $csvContent));
        
        $rows = array_map(function ($line) {
            return str_getcsv($line, ',', '"');
        }, $lines);
        
        $header = array_shift($rows);
        $headerCount = count($header);

        if ($headerCount !== 4) {
            Log::error('Invalid header count', ['header' => $header, 'count' => $headerCount]);
            throw new \Exception('CSV header does not have 4 columns');
        }

        $parsedData = [];
        foreach ($rows as $index => $row) {
            $rowCount = count($row);
            if ($rowCount !== 4) {
                Log::warning('Row length mismatch', [
                    'line' => $index + 2,
                    'row' => $row,
                    'expected' => 4,
                    'actual' => $rowCount,
                ]);
                $row = array_pad($row, 4, '');
            }
            $parsedData[] = array_combine($header, $row);
        }

        return $parsedData;
    }

    protected function storeRecords($data, $modelClass)
    {
        $client = new Client();
        $total = count($data);
        $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/embedding-001:embedContent';
        $apiKey = env('GEMINI_API_KEY');

        foreach ($data as $index => $row) {
            $date = !empty($row['date']) ? $row['date'] : null;
            $name = $row['name_of_person'] ?? '';
            $amount = $row['amount'] ?? '';
            $other = $row['other_information'] ?? '';

            if (!$date && !$name && !$amount && !$other) {
                Log::warning('Skipping empty row', ['row' => $row]);
                continue;
            }

            $structuredText = json_encode(['date' => $date, 'name' => $name, 'amount' => $amount]);
            $fullText = $other ?: $structuredText;

            try {
                $structuredResponse = $client->post($endpoint, [
                    'json' => ['content' => ['parts' => [['text' => $structuredText]]]],
                    'query' => ['key' => $apiKey],
                ]);
                $fullResponse = $client->post($endpoint, [
                    'json' => ['content' => ['parts' => [['text' => $fullText]]]],
                    'query' => ['key' => $apiKey],
                ]);

                $structuredEmbedding = json_decode($structuredResponse->getBody(), true)['embedding']['values'];
                $fullEmbedding = json_decode($fullResponse->getBody(), true)['embedding']['values'];

                $modelClass::create([
                    'reconciliation_project_id' => $this->project->id,
                    'date' => $date,
                    'name_of_person' => $name,
                    'amount' => $amount,
                    'other_information' => $other,
                    'vector_structured' => $structuredEmbedding,
                    'vector_full' => $fullEmbedding,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to store record', [
                    'row' => $row,
                    'error' => $e->getMessage(),
                ]);
            }

            $this->project->update(['progress' => (int)(($index + 1) / $total * 100)]);
        }
    }

    protected function reconcileRecords()
    {
        $result = [];
        $statements = StatementRecord::where('reconciliation_project_id', $this->project->id)->get();
        $total = $statements->count();

        foreach ($statements as $index => $statement) {
            $entry = ['statement_id' => $statement->id];

            $exactMatch = LedgerRecord::where('reconciliation_project_id', $this->project->id)
                ->where('date', $statement->date)
                ->where('name_of_person', $statement->name_of_person)
                ->where('amount', $statement->amount)
                ->first();

            $entry['exact_match'] = $exactMatch
                ? [
                    'ledger_id' => $exactMatch->id,
                    'confidence' => 1.0,
                    'match_type' => 'exact',
                ]
                : [];

            DB::statement('SET ivfflat.probes = 10;');
            $statementVectorString = '[' . implode(',', $statement->vector_structured) . ']';
            $similarLedger = LedgerRecord::where('reconciliation_project_id', $this->project->id)
                ->where('date', $statement->date)
                ->orderByRaw('vector_structured <=> ?', [$statementVectorString])
                ->first();

            if ($similarLedger) {
                $ledgerVectorString = '[' . implode(',', $similarLedger->vector_structured) . ']';
                $distance = DB::selectOne('SELECT CAST(? AS vector) <=> CAST(? AS vector) AS distance', [
                    $statementVectorString,
                    $ledgerVectorString,
                ])->distance;
                $confidence = 1 - $distance;

                $entry['fuzzy_match'] = ($confidence > 0.8)
                    ? [
                        'ledger_id' => $similarLedger->id,
                        'confidence' => $confidence,
                        'match_type' => 'fuzzy',
                    ]
                    : [];
            } else {
                $entry['fuzzy_match'] = [];
            }

            $result[] = $entry;
            $this->project->update(['progress' => (int)(($index + 1) / $total * 100)]);
        }

        return $result;
    }
}