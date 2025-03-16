<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ReconciledRecord;
use App\Models\Reconciliation;
use App\Models\Statement;
use App\Models\Ledger;
use App\Services\ReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;
use Illuminate\Support\Str;
use App\Repositories\Statement\StatementRepository;
use App\Repositories\Ledger\LedgerRepository;
use App\Repositories\MatchingTransaction\MatchingTransactionRepository;
use App\Http\Resources\TransactionResource;

class ReconciliationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_reconcile_with_gemini_returns_successful_response(): void
    {
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

    public function test_export_generation_validation(): void
    {
        $response = $this->post('/api/v1/reconcile/export', [
            'matches' => [],
            'unmatched' => []
        ]);
        $response->assertStatus(422);
    }

    public function test_export_generation(): void
    {
        $response = $this->post('/api/v1/reconcile/export', [
            'data' => [
                'matches' => [
                    [
                        'file1_transaction' => [
                            "Date" => "12/4/2023",
                            "Description" => "Test",
                            "Amount" => "650"
                        ],
                        'status' => "Matched",
                        'file2_transaction' => [
                            "Date" => "12/4/2023",
                            "Description" => "Test",
                            "Amount" => "650"
                        ]
                    ],
                    [
                        'file1_transaction' => [
                            "Date" => "12/4/2023",
                            "Description" => "Test",
                            "Amount" => "650"
                        ],
                        'status' => "Matched",
                        'file2_transaction' => [
                            "Date" => "12/4/2023",
                            "Description" => "Test",
                            "Amount" => "650"
                        ]
                    ],
                    [
                        'file1_transaction' => [
                            "Date" => "12/4/2023",
                            "Description" => "Test",
                            "Amount" => "650"
                        ],
                        'status' => "Matched",
                        'file2_transaction' => [
                            "Date" => "12/4/2023",
                            "Description" => "Test",
                            "Amount" => "650"
                        ]
                    ]
                ],
                'unmatched' => [
                    'unmatched_file1' => [
                        [
                            "Date" => "12/4/2023",
                            "Description" => "Test",
                            "Amount" => "680"
                        ],
                        [
                            "Date" => "12/4/2023",
                            "Description" => "Test",
                            "Amount" => "900"
                        ]
                    ],
                    'unmatched_file2' => [
                        [
                            "Date" => "12/4/2023",
                            "Description" => "Test",
                            "Amount" => "450"
                        ]
                    ]
                ]
            ]
        ]);
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
        $reconcile = Reconciliation::factory()->createOne();
        $user = User::factory()->createOne();
        $this->actingAs($user);

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

        $response = $this->getJson("/api/v1/reconciliations/{$reconcile->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message'     => 'Reconciled records fetched successfully',
                'status'      => 'success',
                'status_code' => 200,
            ])
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'reconciliation_id',
                        'data',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);
    }

    public function test_reconcile_match_transaction()
    {
        // Create test reconciliation
        $reconciliation = Reconciliation::factory()->createOne();
        $record = ReconciledRecord::factory()->createOne([
            'reconciliation_id' => $reconciliation->id
        ]);

        // Prepare test request data
        $payload = [
            'ledger' => [
                'Date' => '2024-12-05',
                'Person' => 'Test Ledger',
                'Amount' => 50000
            ],
            'statement' => [
                'Date' => '2024-12-05',
                'Person' => 'Test Statement',
                'Amount' => 50000
            ],
            'action' => 'match'
        ];

        // Send request
        $response = $this->postJson("/api/v1/reconcile/{$reconciliation->id}", $payload);

        // Assert response
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'status_code' => 200,
                'message' => 'Successfully updated the reconciliation!',
            ]);
    }


    public function test_unmatch_transactions_successfully()
    {
        $reconciliation = Reconciliation::factory()->create();
        $record = ReconciledRecord::factory()->createOne([
            'reconciliation_id' => $reconciliation->id
        ]);

        $data = [
            'ledger' => [
                'Date' => '2024-12-02',
                'Person' => 'Beau',
                'Amount' => 100000
            ],
            'statement' => [
                'Date' => '2024-12-05',
                'Person' => 'Bola',
                'Amount' => 80000
            ],
            'action' => 'unmatch'
        ];

        $response = $this->postJson("/api/v1/reconcile/{$reconciliation->id}", $data);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'status_code',
            'message',
            'data' => [
                'reconciliation_id',
                'matches',
                'matchSummary' => [
                    'totalMatched',
                    'totalUnmatched'
                ],
                'only_in_file1',
                'only_in_file2',
                'unmatched' => [
                    'unmatched_file1',
                    'unmatched_file2'
                ]
            ]
        ]);
    }

    public function test_invalid_action_returns_error()
    {
        $reconciliation = Reconciliation::factory()->create();

        $data = [
            'ledger' => [
                'Date' => '2024-12-02',
                'Person' => 'Beau',
                'Amount' => 100000
            ],
            'statement' => [
                'Date' => '2024-12-05',
                'Person' => 'Bola',
                'Amount' => 80000
            ],
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
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
