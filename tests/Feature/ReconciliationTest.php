<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Http\UploadedFile;

class ReconciliationTest extends TestCase
{

    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->faker = \Faker\Factory::create(); // Initialize Faker manually
    }

    private function generateFakeCsv($rows = 5)
    {
        $csvContent = "Date,Description,Amount\n";

        for ($i = 0; $i < $rows; $i++) {
            $csvContent .= $this->faker->date('Y-m-d') . ",";
            $csvContent .= $this->faker->sentence(3) . ",";
            $csvContent .= $this->faker->randomFloat(2, 10, 1000) . "\n";
        }

        $filePath = storage_path('framework/testing/' . $this->faker->uuid . '.csv');
        file_put_contents($filePath, $csvContent);

        return new UploadedFile($filePath, 'test.csv', 'text/csv', null, true);
    }

    /**
     * Test when no files are provided
     */
    public function test_reconciliation_fails_when_no_files_are_provided()
    {
        $response = $this->postJson('/api/v1/reconcile', []);

        $response->assertStatus(422) // Expecting a bad request
                 ->assertJson([ "data" => [
                        "financial_statement" => [
                            "The financial statement field is required."
                        ],
                        "company_ledger" => [
                            "The company ledger field is required."
                        ]
                 ]
                 ]);
    }

    /**
     * Test when only one file is provided
     */
    public function test_reconciliation_fails_when_only_one_file_is_provided()
    {
        $response = $this->postJson('/api/v1/reconcile', [
            'financial_statement' => $this->generateFakeCsv(4),
        ]);

        $response->assertStatus(422) // Expecting a bad request
                 ->assertJson([ "data" => [
                        "company_ledger" => [
                            "The company ledger field is required."
                        ]
                 ]
                 ]);

    }

    /**
     * Test when invalid file format is provided
     */
    public function test_reconciliation_fails_when_invalid_file_is_provided()
    {
        $invalidFile = UploadedFile::fake()->create('invalid.txt', 100, 'text/plain');

        $response = $this->postJson('/api/v1/reconcile', [
            'financial_statement' => $invalidFile,
            'company_ledger' => $invalidFile,
        ]);

        $response->assertStatus(422) // Unprocessable Entity
                 ->assertJson([ "data" => [
                     "financial_statement" => [
                         "Financial statement must be a CSV file"
                        ],
                        "company_ledger" => [
                            "Company Ledger must be a CSV file."
                        ]
                 ]
                 ]);
    }

    /**
     * Test successful reconciliation
     */
    public function test_reconciliation_succeeds_with_valid_files()
    {
        $bankStatement = $this->generateFakeCsv(4);
        $companyLedger = $this->generateFakeCsv(4);

        $response = $this->postJson('/api/v1/reconcile', [
            'financial_statement' => $bankStatement,
            'company_ledger' => $companyLedger,
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                        'reconciliationSummary' => [
                             'totalMatchedTransactions',
                            'totalUnmatchedTransactions',
                            'matchedTransactions' => [
                                '*' => [
                                     'bankStatement',
                                    'companyLedger',
                                    'status'
                                ]
                        ]
                        ],
                 ]]);
    }
}
