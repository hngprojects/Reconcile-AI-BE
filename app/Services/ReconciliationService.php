<?php

namespace App\Services;

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
        $fullPath = storage_path("app/$path");
        if (!file_exists($fullPath)) {
            return "Error: File not found!";
        }

        $rows = array_map('str_getcsv', file($fullPath));
        $header = array_shift($rows);
        $data = array_map(fn($row) => array_combine($header, $row), $rows);

        return json_encode($data, JSON_PRETTY_PRINT);
    }

    public function reconcileFiles($bankStatementPath, $ledgerPath)
    {
        $bankStatementJson = $this->csvToJson($bankStatementPath);
        $ledgerJson = $this->csvToJson($ledgerPath);
        $data = [
            "reconciliationSummary" => [
                "totalMatchedTransactions" => 5,
                "totalUnmatchedTransactions" => 1,
                "matchedTransactions" => [
                    [
                        "bankStatement" => [
                            "date" => "2025-03-01",
                            "description" => "Deposit - Client A",
                            "amount" => 5000.0
                        ],
                        "companyLedger" => [
                            "date" => "2025-03-01",
                            "description" => "Deposit - Client A",
                            "amount" => 5000.0
                        ],
                        "status" => "Matched"
                    ],
                    [
                        "bankStatement" => [
                            "date" => "2025-03-02",
                            "description" => "Withdrawal - Rent",
                            "amount" => -2000.0
                        ],
                        "companyLedger" => [
                            "date" => "2025-03-02",
                            "description" => "Withdrawal - Rent",
                            "amount" => -2000.0
                        ],
                        "status" => "Matched"
                    ],
                    [
                        "bankStatement" => [
                            "date" => "2025-03-03",
                            "description" => "Deposit - Client B",
                            "amount" => 3000.0
                        ],
                        "companyLedger" => [
                            "date" => "2025-03-03",
                            "description" => "Deposit - Client B",
                            "amount" => 3000.0
                        ],
                        "status" => "Matched"
                    ],
                    [
                        "bankStatement" => [
                            "date" => "2025-03-04",
                            "description" => "Card Payment - Supplies",
                            "amount" => -500.0
                        ],
                        "companyLedger" => [
                            "date" => "2025-03-04",
                            "description" => "Card Payment - Supplies",
                            "amount" => -500.0
                        ],
                        "status" => "Matched"
                    ],
                    [
                        "bankStatement" => [
                            "date" => "2025-03-05",
                            "description" => "Deposit - Client C",
                            "amount" => 4000.0
                        ],
                        "companyLedger" => [
                            "date" => "2025-03-05",
                            "description" => "Deposit - Client C",
                            "amount" => 4000.0
                        ],
                        "status" => "Matched"
                    ]
                ],
                "unmatchedTransactions" => [
                    [
                        "bankStatement" => [
                            "date" => "2025-03-06",
                            "description" => "Bank Fees",
                            "amount" => -50.0
                        ],
                        "companyLedger" => [],
                        "status" => "Unmatched"
                    ]
                ]
            ]
        ];

        // Convert to JSON
        $json = json_encode($data, JSON_PRETTY_PRINT);

        $prompt = "Compare these: $bankStatementJson and $ledgerJson by following these instructions for reconciliation then return a valid JSON:

Dynamic Column Mapping:

The system should automatically detect and map columns based on the uploaded file headers. For example:

If the bank statement has columns like \"Date\", \"Narration\", and \"Amount\", the system will automatically map them to the appropriate fields in the company ledger.

The company ledger might contain columns like \"Client Name\", \"Amount\", \"Invoice Number\", \"Description\", or any other custom header the user uploads.

Transaction Matching Logic:

Amount Matching: The system will compare Amount from both the bank statement and company ledger. If the amounts match exactly or are within an acceptable threshold (e.g., 0.01 variance), consider it a match.

Description/Narration Matching: The system will compare the Narration (from the bank statement) to the Description (from the company ledger). If they are similar (case-insensitive, ignoring certain keywords), mark them as matched.

Date Matching: The system will compare Transaction Dates from both files. Exact matches will be considered valid, while transactions with a slight date discrepancy (e.g., within a few days) will also be considered for matching.

Handle Multiple Columns:

If the company ledger contains extra columns like \"Client Name\", \"Invoice Number\", or any other column, these can be displayed for informational purposes but will not impact the reconciliation logic. Only the Amount and Description (or equivalent) fields will be used for matching transactions.

For example, if the company ledger includes a \"Client Name\" or \"Student Name\" column, it will be displayed in the reconciliation results but will not be used for matching. The AI will focus on the relevant fields for the reconciliation process (e.g., Amount, Description, Date, Invoice Number, Narration).

Identify and Highlight Unmatched Transactions:

If transactions don’t match based on Amount, Description, Invoice Number, or Date, they will be flagged as Unmatched and highlighted in red for user review.

Matched Transactions:

Transactions that the AI successfully matches based on Amount, Description, and Date should be marked as \"Matched\" in green.

Provide a green status tag for those transactions that are correctly matched.

Reconciliation Summary:

After performing the reconciliation, provide a summary showing the total number of matched transactions and the number of unmatched transactions.

Calculate and display the accuracy rate, which should aim for 60% accuracy for the first version of the AI-powered reconciliation. Only respond with a response in that follows this Response Format, nothing more, nothing else, I only need the response.' The final output should be returned as a JSON object with the following structure: $json Please only return a valid JSON";
        $aiResponse = Gemini::geminiFlash()->generateContent($prompt);

        $jsonString = str_replace('\n', '', preg_replace('/```(?:json)?\s*([\s\S]*?)```/i', '$1', trim($aiResponse->text())));

        $formatted = json_decode($jsonString, true);

        return $formatted;
    }
}
