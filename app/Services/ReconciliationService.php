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
        if (!file_exists($path)) {
            return "Error: File not found!";
        }

        $rows = array_map('str_getcsv', file($path));
        $header = array_shift($rows);
        $data = array_map(fn($row) => array_combine($header, $row), $rows);

        return json_encode($data, JSON_PRETTY_PRINT);
    }

    public function reconcileFiles($bankStatementPath, $ledgerPath)
    {
        ini_set('max_execution_time', 300);
        $bankStatementJson = $this->csvToJson($bankStatementPath);
        $ledgerJson = $this->csvToJson($ledgerPath);

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

        $aiResponse = Gemini::geminiFlash()->generateContent($prompt);

        $jsonString = str_replace('\n', '', preg_replace('/```(?:json)?\s*([\s\S]*?)```/i', '$1', trim($aiResponse->text())));

        $formatted = json_decode($jsonString, true);

        return $formatted;
    }
}
