<?php

namespace Tests\Feature;

use App\Jobs\ProcessReconciliation;
use Tests\TestCase;
use App\Services\NewReconciliation\NewReconciliationServiceImplement;
use App\Repositories\Reconciliation\ReconciliationRepository;
use App\Repositories\UserFile\UserFileRepository;
use App\Repositories\Ledger\LedgerRepository;
use App\Repositories\LedgerPayment\LedgerPaymentRepository;
use App\Repositories\Statement\StatementRepository;
use App\Repositories\StatementFile\StatementFileRepository;
use App\Repositories\MatchingTransaction\MatchingTransactionRepository;
use App\Models\Reconciliation;
use App\Models\ReconciledRecord;
use App\Models\BookkeepingLedger;
use App\Models\BankAccount;
use App\Models\User;
use App\Models\PaymentPlan;
use App\Models\Plan;
use App\Models\Ledger;
use App\Models\Statement;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Mockery;

class EmbeddingsTest extends TestCase
{
    protected $service;
    protected $reconciliationRepository;
    protected $userFileRepository;
    protected $ledgerRepository;
    protected $ledgerPaymentRepository;
    protected $statementRepository;
    protected $statementFileRepository;
    protected $matchedRepository;

    protected $user;
    protected $planBusiness;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reconciliationRepository = Mockery::mock(ReconciliationRepository::class);
        $this->userFileRepository = Mockery::mock(UserFileRepository::class);
        $this->ledgerRepository = Mockery::mock(LedgerRepository::class);
        $this->ledgerPaymentRepository = Mockery::mock(LedgerPaymentRepository::class);
        $this->statementRepository = Mockery::mock(StatementRepository::class);
        $this->statementFileRepository = Mockery::mock(StatementFileRepository::class);
        $this->matchedRepository = Mockery::mock(MatchingTransactionRepository::class);

        $this->service = new NewReconciliationServiceImplement(
            $this->reconciliationRepository,
            $this->userFileRepository,
            $this->ledgerRepository,
            $this->ledgerPaymentRepository,
            $this->statementRepository,
            $this->statementFileRepository,
            $this->matchedRepository
        );

        $this->planBusiness = Plan::factory()->create([
            'plan' => 'Business',
            'reconciliations_per_month' => -1 // Unlimited
        ]);

        // Create a user
        $this->user = User::factory()->create();

        Storage::fake('local');
        Queue::fake(); // Prevent jobs from actually running during test
    }

    public function test_reconcile_embeddings_successful()
    {
        $statementFile = UploadedFile::fake()->create('statement.csv');

        PaymentPlan::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->planBusiness->id,
            'start_date' => now(),
            'expire_date' => now()->addMonth(30),
            'is_active' => true
        ]);

        $mockReconciliation = new Reconciliation(['id' => 1]); // Ensure valid instance

        $this->reconciliationRepository
            ->shouldReceive('store')
            ->andReturn($mockReconciliation);

        $this->userFileRepository
            ->shouldReceive('store')
            ->andReturnUsing(function ($data) {
                return (object) ['file_name' => $data['file_name'], 'type' => $data['type']];
            });

        $this->statementRepository
            ->shouldReceive('findAll')
            ->andReturn(collect([]));

        $this->ledgerRepository
            ->shouldReceive('findAllByType')
            ->andReturn(collect([]));

        $this->mock(Gemini::class, function ($mock) {
            $mock->shouldReceive('embeddingModel->embedContent')
                ->andReturn((object) ['embedding' => (object) ['values' => [0.1, 0.2, 0.3]]]);
        });
        $ledg = BookkeepingLedger::factory()->create();
        $acc = BankAccount::factory()->create();

        $response = $this->actingAs($this->user)->postJson('/api/v1/reconcile', [
            'bank_statements' => [
                [
                    'file' => $statementFile,
                    'bank_account' => $acc->id,
                    'period' => [
                        'from' => now()->subMonth()->format('Y-m-d'),
                        'to' => now()->format('Y-m-d')
                    ],
                    'mapper' => [
                        'date' => 'Date',
                        'description' => 'Description',
                        'amount' => 'Amount'
                    ]
                ]
            ],
            'ledgers' => [$ledg->id],
            'title' => 'Reconciliation Test'
        ]);

        // Assert the job was dispatched
        Queue::assertPushed(ProcessReconciliation::class);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'status_code',
                'message',
                'data' => ['reconciliation_id']
            ]);
    }

    public function test_reconcile_embeddings_invalid_file()
    {
        $invalidFile = UploadedFile::fake()->create('invalid.txt');
        $user = User::factory()->create();
        $plan = PaymentPlan::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->postJson('/api/v1/reconcile', [
            'statement' => $invalidFile,
            'ledger' => $invalidFile,
        ]);

        $response->assertStatus(422);
    }

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
            'matches' => [
                [
                    'ledger' => $ledger->id,
                    'statement' => $statement->id,
                    'matched_by' => 'manual',
                    'score' => 100,
                    'action' => 'match'
                ]
            ],
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
            'matches' => [
                [
                    'ledger' => $ledger->id,
                    'statement' => $statement->id,
                    'matched_by' => 'manual',
                    'score' => 100,
                    'action' => 'match'
                ]
            ],
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
            'matches' => [
                [
                    'ledger' => $ledger->id,
                    'statement' => $statement->id,
                    'matched_by' => 'manual',
                    'score' => 100,
                    'action' => 'invalid_action'
                ]
            ],
        ];

        $response = $this->postJson("/api/v1/reconcile/{$reconciliation->id}", $data);

        $response->assertStatus(422);
        $response->assertJson([
            "message" => "The selected matches.0.action is invalid.",
            "errors" => [
                "matches.0.action" => [
                    "The selected matches.0.action is invalid."
                ]
            ]
        ]);
    }
}
