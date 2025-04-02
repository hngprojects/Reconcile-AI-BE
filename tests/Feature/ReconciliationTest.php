<?php

namespace Tests\Feature;

<<<<<<< HEAD
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Http\UploadedFile;

class ReconciliationTest extends TestCase
{

    use WithFaker;
=======
use App\Models\User;
use App\Models\ReconciledRecord;
use App\Models\Reconciliation;
use App\Models\Statement;
use App\Models\Ledger;
use App\Services\ReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Event;

use App\Models\PaymentPlan;
use App\Models\Plan;

class ReconciliationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Plan $planStarter;
    protected Plan $planBusiness;
    protected ReconciliationService $mockService;
>>>>>>> 6ad9c69c36498c12ca59168b25484bf77bdaaf61

    protected function setUp(): void
    {
        parent::setUp();
<<<<<<< HEAD
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
                            'accuracyRate'
                        ],
                 ]]);
=======

        // Speed up tests by not running event listeners
        Event::fake();

        Storage::fake('local');
        $this->user = User::factory()->create();
        $this->planStarter = Plan::factory()->create([
            'plan' => 'Starter',
            'reconciliations_per_month' => 5
        ]);

        $this->planBusiness = Plan::factory()->create([
            'plan' => 'Business Pro',
            'reconciliations_per_month' => -1
        ]);

        // Create active payment plan
        PaymentPlan::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->planBusiness->id,
            'is_active' => true
        ]);

        // Create mock service
        $this->mockService = Mockery::mock(ReconciliationService::class);
        $this->app->instance(ReconciliationService::class, $this->mockService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_reconcile_with_gemini_returns_successful_response(): void
    {
        $this->actingAs($this->user);
        PaymentPlan::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->planBusiness->id,
            'is_active' => true
        ]);
        $mockService = Mockery::mock(ReconciliationService::class);
        $mockService->shouldReceive('reconcileWithGemini')
            ->once()
            ->andReturn([
                'matches'       => [
                    [
                        'file1_transaction' => ['name' => 'John Doe', 'amount' => 100],
                        'file2_transaction' => ['name' => 'Doe John', 'amount' => 100],
                        'match_score'       => 95,
                    ],
                ],
                'only_in_file1' => [['name' => 'Alice Brown', 'amount' => 300]],
                'only_in_file2' => [['name' => 'Bob Martin', 'amount' => 400]],
                'unmatched'     => [
                    'unmatched_file1' => [['name' => 'Alice Brown', 'amount' => 300]],
                    'unmatched_file2' => [['name' => 'Bob Martin', 'amount' => 400]],
                ],
                'matchSummary'  => [
                    'totalMatched'        => 1,
                    'totalUnmatchedFile1' => 1,
                    'totalUnmatchedFile2' => 1,
                    'totalUnmatched'      => 2,
                ],
            ]);

        $mockService->shouldReceive('store')
                    ->once()
                    ->with(Mockery::type('array'))
                    ->andReturn(new Reconciliation(['id' => Str::uuid()]));

        $this->app->instance(ReconciliationService::class, $mockService);

        $file1 = UploadedFile::fake()->create('file1.csv', 100);
        $file2 = UploadedFile::fake()->create('file2.csv', 100);

        $response = $this->postJson('/api/v1/reconcile', [
            'file1'            => $file1,
            'file2'            => $file2,
            'reconcile_option' => 'reconcile_with_Gemini',
        ]);


        $response->assertStatus(200)
            ->assertJson([
                'message'     => 'Reconciliation successful',
                'status'      => 'success',
                'status_code' => 200,
                'data'        => [
                    'matches'       => [
                        [
                            'file1_transaction' => ['name' => 'John Doe', 'amount' => 100],
                            'file2_transaction' => ['name' => 'Doe John', 'amount' => 100],
                            'match_score'       => 95,
                        ],
                    ],
                    'only_in_file1' => [['name' => 'Alice Brown', 'amount' => 300]],
                    'only_in_file2' => [['name' => 'Bob Martin', 'amount' => 400]],
                    'unmatched'     => [
                        'unmatched_file1' => [['name' => 'Alice Brown', 'amount' => 300]],
                        'unmatched_file2' => [['name' => 'Bob Martin', 'amount' => 400]],
                    ],
                    'matchSummary'  => [
                        'totalMatched'        => 1,
                        'totalUnmatchedFile1' => 1,
                        'totalUnmatchedFile2' => 1,
                        'totalUnmatched'      => 2,
                    ],
                ],
            ]);
    }

    public function test_reconcile_with_openai_returns_successful_response(): void
    {
        $this->actingAs($this->user);
        PaymentPlan::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->planBusiness->id,
            'is_active' => true
        ]);
        $mockService = Mockery::mock(ReconciliationService::class);
        $mockService->shouldReceive('reconcileWithOpenAI')
            ->once()
            ->andReturn([
                'reconciliation_id' => Str::uuid(),
                'matches'       => [
                    [
                        'file1_transaction' => ['name' => 'Jane Smith', 'amount' => 200],
                        'file2_transaction' => ['name' => 'Jane Smith', 'amount' => 200],
                        'match_score'       => 100,
                    ],
                ],
                'only_in_file1' => [['name' => 'Alice Brown', 'amount' => 300]],
                'only_in_file2' => [['name' => 'Bob Martin', 'amount' => 400]],
            ]);


        $mockService->shouldReceive('store')
                    ->once()
                    ->with(Mockery::type('array'))
                    ->andReturn(new Reconciliation(['id' => Str::uuid()]));
        $this->app->instance(ReconciliationService::class, $mockService);

        $file1 = UploadedFile::fake()->create('file1.csv', 100);
        $file2 = UploadedFile::fake()->create('file2.csv', 100);

        $response = $this->postJson('/api/v1/reconcile', [
            'file1'            => $file1,
            'file2'            => $file2,
            'reconcile_option' => 'reconcile_with_openAI',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message'     => 'Reconciliation successful',
                'status'      => 'success',
                'status_code' => 200,
                'data'        => [
                    'matches'       => [
                        [
                            'file1_transaction' => ['name' => 'Jane Smith', 'amount' => 200],
                            'file2_transaction' => ['name' => 'Jane Smith', 'amount' => 200],
                            'match_score'       => 100,
                        ],
                    ],
                    'only_in_file1' => [['name' => 'Alice Brown', 'amount' => 300]],
                    'only_in_file2' => [['name' => 'Bob Martin', 'amount' => 400]],
                ],
            ]);
    }

    public function test_reconcile_with_deepseek_returns_successful_response(): void
    {
        $this->actingAs($this->user);
        PaymentPlan::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->planBusiness->id,
            'is_active' => true
        ]);
        $mockService = Mockery::mock(ReconciliationService::class);
        $mockService->shouldReceive('reconcileWithDeepSeek')
            ->once()
            ->andReturn([
                'matches'          => [
                    [
                        'file1_transaction' => ['name' => 'Charlie Brown', 'amount' => 500],
                        'file2_transaction' => ['name' => 'Brown Charlie', 'amount' => 500],
                        'match_score'       => 90,
                    ],
                ],
                'only_in_file1'    => [['name' => 'David Jones', 'amount' => 600]],
                'only_in_file2'    => [['name' => 'Eve Wilson', 'amount' => 700]],
                'decoded_response' => [
                    'matches' => [
                        [
                            'file1_transaction' => ['name' => 'Charlie Brown', 'amount' => 500],
                            'file2_transaction' => ['name' => 'Brown Charlie', 'amount' => 500],
                            'match_score'       => 90,
                        ],
                    ],
                ],
            ]);


        $mockService->shouldReceive('store')
                    ->once()
                    ->with(Mockery::type('array'))
                    ->andReturn(new Reconciliation(['id' => Str::uuid()]));
        $this->app->instance(ReconciliationService::class, $mockService);

        $file1 = UploadedFile::fake()->create('file1.csv', 100);
        $file2 = UploadedFile::fake()->create('file2.csv', 100);

        $response = $this->postJson('/api/v1/reconcile', [
            'file1'            => $file1,
            'file2'            => $file2,
            'reconcile_option' => 'reconcile_with_deepSeek',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message'     => 'Reconciliation successful',
                'status'      => 'success',
                'status_code' => 200,
                'data'        => [
                    'matches'       => [
                        [
                            'file1_transaction' => ['name' => 'Charlie Brown', 'amount' => 500],
                            'file2_transaction' => ['name' => 'Brown Charlie', 'amount' => 500],
                            'match_score'       => 90,
                        ],
                    ],
                    'only_in_file1' => [['name' => 'David Jones', 'amount' => 600]],
                    'only_in_file2' => [['name' => 'Eve Wilson', 'amount' => 700]],
                ],
            ]);
    }

    public function test_reconcile_with_recox_returns_successful_response(): void
    {
        $this->actingAs($this->user);
        PaymentPlan::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->planBusiness->id,
            'is_active' => true
        ]);
        $mockService = Mockery::mock(ReconciliationService::class);
        $mockService->shouldReceive('reconcileWithRecox')
            ->once()
            ->andReturn([
                'matches'       => [
                    [
                        'file1_transaction' => ['name' => 'Frank Miller', 'amount' => 800],
                        'file2_transaction' => ['name' => 'Miller Frank', 'amount' => 800],
                        'match_score'       => 85,
                    ],
                ],
                'matches_count' => 1,
                'only_in_file1' => [['name' => 'Grace Lee', 'amount' => 900]],
                'only_in_file2' => [['name' => 'Henry Davis', 'amount' => 1000]],
            ]);


        $mockService->shouldReceive('store')
                    ->once()
                    ->with(Mockery::type('array'))
                    ->andReturn(new Reconciliation(['id' => Str::uuid()]));
        $this->app->instance(ReconciliationService::class, $mockService);

        $file1 = UploadedFile::fake()->create('file1.csv', 100);
        $file2 = UploadedFile::fake()->create('file2.csv', 100);

        $response = $this->postJson('/api/v1/reconcile', [
            'file1'            => $file1,
            'file2'            => $file2,
            'reconcile_option' => 'reconcile_with_recox_ai',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message'     => 'Reconciliation successful',
                'status'      => 'success',
                'status_code' => 200,
                'data'        => [
                    'matches'       => [
                        [
                            'file1_transaction' => ['name' => 'Frank Miller', 'amount' => 800],
                            'file2_transaction' => ['name' => 'Miller Frank', 'amount' => 800],
                            'match_score'       => 85,
                        ],
                    ],
                    'matches_count' => 1,
                    'only_in_file1' => [['name' => 'Grace Lee', 'amount' => 900]],
                    'only_in_file2' => [['name' => 'Henry Davis', 'amount' => 1000]],
                ],
            ]);
    }

    public function test_defaults_to_gemini_when_no_option_provided(): void
    {
        $this->actingAs($this->user);
        PaymentPlan::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->planBusiness->id,
            'is_active' => true
        ]);
        $mockService = Mockery::mock(ReconciliationService::class);
        $mockService->shouldReceive('reconcileWithGemini')
            ->once()
            ->andReturn([
                'matches'       => [
                    [
                        'file1_transaction' => ['name' => 'John Doe', 'amount' => 100],
                        'file2_transaction' => ['name' => 'Doe John', 'amount' => 100],
                        'match_score'       => 95,
                    ],
                ],
                'only_in_file1' => [],
                'only_in_file2' => [],
                'unmatched'     => [
                    'unmatched_file1' => [],
                    'unmatched_file2' => [],
                ],
                'matchSummary'  => [
                    'totalMatched'        => 1,
                    'totalUnmatchedFile1' => 0,
                    'totalUnmatchedFile2' => 0,
                    'totalUnmatched'      => 0,
                ],
            ]);


        $mockService->shouldReceive('store')
                    ->once()
                    ->with(Mockery::type('array'))
                    ->andReturn(new Reconciliation(['id' => Str::uuid()]));
        $this->app->instance(ReconciliationService::class, $mockService);

        $file1 = UploadedFile::fake()->create('file1.csv', 100);
        $file2 = UploadedFile::fake()->create('file2.csv', 100);

        $response = $this->postJson('/api/v1/reconcile', [
            'file1' => $file1,
            'file2' => $file2,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message'     => 'Reconciliation successful',
                'status'      => 'success',
                'status_code' => 200,
            ]);
    }

    public function test_validates_file_types(): void
    {
        $this->actingAs($this->user);
        PaymentPlan::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->planBusiness->id,
            'is_active' => true
        ]);
        $file1 = UploadedFile::fake()->create('file1.pdf', 100);
        $file2 = UploadedFile::fake()->create('file2.csv', 100);

        $response = $this->postJson('/api/v1/reconcile', [
            'file1' => $file1,
            'file2' => $file2,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file1']);
    }

    public function test_validates_reconcile_option(): void
    {
        $this->actingAs($this->user);
        PaymentPlan::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->planBusiness->id,
            'is_active' => true
        ]);
        $file1 = UploadedFile::fake()->create('file1.csv', 100);
        $file2 = UploadedFile::fake()->create('file2.csv', 100);

        $response = $this->postJson('/api/v1/reconcile', [
            'file1'            => $file1,
            'file2'            => $file2,
            'reconcile_option' => 'invalid_option',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reconcile_option']);
    }

    public function test_handles_invalid_file_format(): void
    {
        $this->actingAs($this->user);
        PaymentPlan::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->planBusiness->id,
            'is_active' => true
        ]);
        $invalidContent = "This is not a valid CSV file";
        $file1          = UploadedFile::fake()->createWithContent('file1.csv', $invalidContent);
        $file2          = UploadedFile::fake()->createWithContent('file2.csv', $invalidContent);

        $mockService = Mockery::mock(ReconciliationService::class)
            ->shouldAllowMockingProtectedMethods();

        $mockService->shouldReceive('loadFile')
            ->andThrow(new \Exception('Unsupported file format.'));

        $mockService->shouldReceive('reconcileWithGemini')
            ->andThrow(new \Exception('Unsupported file format.'));

        $this->app->instance(ReconciliationService::class, $mockService);

        $response = $this->postJson('/api/v1/reconcile', [
            'file1' => $file1,
            'file2' => $file2,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Unsupported file format.',
            ]);
    }

    public function test_handles_exception_during_reconciliation(): void
    {
        $this->actingAs($this->user);
        PaymentPlan::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->planBusiness->id,
            'is_active' => true
        ]);
        $mockService = Mockery::mock(ReconciliationService::class);
        $mockService->shouldReceive('reconcileWithGemini')
            ->once()
            ->andThrow(new \Exception('Test exception'));
        $this->app->instance(ReconciliationService::class, $mockService);

        $file1 = UploadedFile::fake()->create('file1.csv', 100);
        $file2 = UploadedFile::fake()->create('file2.csv', 100);

        $response = $this->postJson('/api/v1/reconcile', [
            'file1'            => $file1,
            'file2'            => $file2,
            'reconcile_option' => 'reconcile_with_Gemini',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Test exception',
            ]);
    }

    public function test_requires_both_files(): void
    {
        $this->actingAs($this->user);
        PaymentPlan::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->planBusiness->id,
            'is_active' => true
        ]);
        $file1 = UploadedFile::fake()->create('file1.csv', 100);

        $response = $this->postJson('/api/v1/reconcile', [
            'file1' => $file1,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file2']);
    }

    /*
    public function test_unauthenticated_requests_are_throttled(): void
    {
        Cache::forget('throttle_unauthenticated_127.0.0.1');
        $cacheKey = 'throttle_unauthenticated_127.0.0.1';
        Cache::put($cacheKey, 0);

        $file1 = UploadedFile::fake()->create('file1.csv', 100);
        $file2 = UploadedFile::fake()->create('file2.csv', 100);

        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/v1/reconcile', [
                'file1' => $file1,
                'file2' => $file2,
            ], ['REMOTE_ADDR' => '127.0.0.1']);
        }

        $response = $this->postJson('/api/v1/reconcile', [
            'file1' => $file1,
            'file2' => $file2,
        ], ['REMOTE_ADDR' => '127.0.0.1']);

        dd(Cache::get($cacheKey))

        $response->assertStatus(429);
        $response->assertJson(['message' => 'maximum number of request reached. Please login to continue']);

        $this->assertEquals(5, Cache::get($cacheKey));
    }

    public function test_authenticated_requests_are_not_throttled(): void
    {
        Cache::forget('throttle_unauthenticated_127.0.0.1');
        $cacheKey = 'throttle_unauthenticated_127.0.0.1';
        Cache::put($cacheKey, 0);

        $file1 = UploadedFile::fake()->create('file1.csv', 100);
        $file2 = UploadedFile::fake()->create('file2.csv', 100);

        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson(route('reconcile'), [
                'file1' => $file1,
                'file2' => $file2,
                'reconcile_option' => 'reconcile_with_recox_ai',
            ]);
        }

        $response = $this->postJson(route('reconcile'), [
            'file1' => $file1,
            'file2' => $file2,
            'reconcile_option' => 'reconcile_with_recox_ai',
        ]);

        $response->assertStatus(429);
        $response->assertJson(['message' => 'maximum number of request reached. Please login to continue']);

        $this->assertEquals(5, Cache::get($cacheKey));
    } */

    public function test_export_generation(): void
    {
        $record = ReconciledRecord::factory()->create();
        $response = $this->get("/api/v1/reconciliations/{$record->reconciliation->id}/export");
        // $file = "reconciled-data-" . now()->format('Y-m-d_H-i-s') . ".csv";
        // $response->assertStatus(200);
        // $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        // $response->assertHeader('Content-Disposition', "attachment; filename=$file");

        // $response->assertDownload($file);

        // Assert response status
        $response->assertStatus(200);

        // Assert content type
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

        // Check Content-Disposition header dynamically
        $response->assertHeader('Content-Disposition');
        $headerValue = $response->headers->get('Content-Disposition');

        // Extract actual filename from header
        preg_match('/filename=(.*)/', $headerValue, $matches);
        $actualFilename = trim($matches[1] ?? '');

        // Ensure filename starts with expected format, ignoring seconds
        $expectedPrefix = 'reconciled-data-' . now()->format('Y-m-d_H-i');
        $this->assertStringStartsWith($expectedPrefix, $actualFilename);
    }

    public function test_reconciled_records_are_saved_for_logged_in_users(): void
    {
        $user = User::factory()->createOne();
        $this->actingAs($user);
        PaymentPlan::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $this->planBusiness->id,
            'is_active' => true
        ]);

        $mockService = Mockery::mock(ReconciliationService::class);
        $mockService->shouldReceive('reconcileWithGemini')
            ->once()
            ->andReturn([
                'matches'       => [
                    [
                        'file1_transaction' => ['name' => 'John Doe', 'amount' => 100],
                        'file2_transaction' => ['name' => 'Doe John', 'amount' => 100],
                        'match_score'       => 95,
                    ],
                ],
                'only_in_file1' => [['name' => 'Alice Brown', 'amount' => 300]],
                'only_in_file2' => [['name' => 'Bob Martin', 'amount' => 400]],
                'unmatched'     => [
                    'unmatched_file1' => [['name' => 'Alice Brown', 'amount' => 300]],
                    'unmatched_file2' => [['name' => 'Bob Martin', 'amount' => 400]],
                ],
                'matchSummary'  => [
                    'totalMatched'        => 1,
                    'totalUnmatchedFile1' => 1,
                    'totalUnmatchedFile2' => 1,
                    'totalUnmatched'      => 2,
                ],
            ]);

        $mockService->shouldReceive('store')
                    ->once()
                    ->with(Mockery::type('array'))
                    ->andReturn(new Reconciliation(['id' => Str::uuid()]));
        $this->app->instance(ReconciliationService::class, $mockService);

        $file1 = UploadedFile::fake()->create('file1.csv', 100);
        $file2 = UploadedFile::fake()->create('file2.csv', 100);

        $response = $this->postJson('/api/v1/reconcile', [
            'file1'            => $file1,
            'file2'            => $file2,
            'reconcile_option' => 'reconcile_with_Gemini',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message'     => 'Reconciliation successful',
                'status'      => 'success',
                'status_code' => 200,
            ]);
    }

    public function test_fetching_reconciled_records_for_logged_in_users(): void
    {
        $user = User::factory()->createOne();
        $this->actingAs($user);
        $reconcile = Reconciliation::factory()->createOne([ 'user_id' => $user->id ]);

        ReconciledRecord::factory()->create([
            'reconciliation_id' => $reconcile->id,
            'data'    => [
                'matches'       => [
                    [
                        'file1_transaction' => ['name' => 'John Doe', 'amount' => 100],
                        'file2_transaction' => ['name' => 'Doe John', 'amount' => 100],
                        'match_score'       => 95,
                    ],
                ],
                'unmatched_statements' => [['name' => 'Alice Brown', 'amount' => 300]],
                'unmatched_ledgers' => [['name' => 'Bob Martin', 'amount' => 400]],                'summary'  => [
                    'totalMatched'        => 1,
                    'totalUnmatchedFile1' => 1,
                    'totalUnmatchedFile2' => 1,
                    'totalUnmatched'      => 2,
                ],
            ],
        ]);

        $response = $this->getJson("/api/v1/reconciliations/{$reconcile->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message'     => 'Reconciled records fetched successfully',
                'status'      => 'success',
                'status_code' => 200,
            ])
            ->assertJsonStructure([
                'data' => [
                    'reconciliation_id',
                    'matches',
                    'summary'
                ]
            ]);
    }

    public function test_reconcile_match_transaction()
    {
        // Create test reconciliation
        $reconciliation = Reconciliation::factory()->createOne();
        $statement = Statement::factory()->create();
        $ledger = Ledger::factory()->create();
        $record = ReconciledRecord::factory()->createOne([
            'reconciliation_id' => $reconciliation->id
        ]);

        // Prepare test request data
        $payload = [
            'ledgers' => [$ledger->id],
            'statements' => [$statement->id],
            'action' => 'match'
        ];

        // Send request
        $response = $this->postJson("/api/v1/reconcile/{$reconciliation->id}", $payload);

        // Assert response
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'status_code' => 200,
                'message' => 'Reconciliation updated successfully',
            ]);
    }


    public function test_unmatch_transactions_successfully()
    {
        $reconciliation = Reconciliation::factory()->create();
        $statement = Statement::factory()->create();
        $ledger = Ledger::factory()->create();
        $record = ReconciledRecord::factory()->createOne([
            'reconciliation_id' => $reconciliation->id
        ]);

        $data = [
            'ledgers' => [$ledger->id],
            'statements' => [$statement->id],
            'action' => 'unmatch'
        ];

        $response = $this->postJson("/api/v1/reconcile/{$reconciliation->id}", $data);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'status_code',
            'message',
            'data'
        ]);
    }

    public function test_invalid_action_returns_error()
    {
        $reconciliation = Reconciliation::factory()->create();
        $statement = Statement::factory()->create();
        $ledger = Ledger::factory()->create();

        $data = [
            'ledgers' => [$ledger->id],
            'statements' => [$statement->id],
            'action' => 'invalid_action'
        ];

        $response = $this->postJson("/api/v1/reconcile/{$reconciliation->id}", $data);

        $response->assertStatus(422);
        $response->assertJson([
            "message" => "The selected action is invalid.",
            "errors" => [
                "action"=> [
                    "The selected action is invalid."
                ]
            ]
        ]);
>>>>>>> 6ad9c69c36498c12ca59168b25484bf77bdaaf61
    }
}
