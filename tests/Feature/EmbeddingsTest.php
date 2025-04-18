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
use App\Repositories\MatchingTransaction\MatchingTransactionRepository;
use App\Models\Reconciliation;
use App\Models\User;
use App\Models\PaymentPlan;
use App\Models\Plan;
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
        $this->matchedRepository = Mockery::mock(MatchingTransactionRepository::class);

        $this->service = new NewReconciliationServiceImplement(
            $this->reconciliationRepository,
            $this->userFileRepository,
            $this->ledgerRepository,
            $this->ledgerPaymentRepository,
            $this->statementRepository,
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
        $ledgerFile = UploadedFile::fake()->create('ledger.csv');

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
            ->shouldReceive('findAll')
            ->andReturn(collect([]));

        $this->mock(Gemini::class, function ($mock) {
            $mock->shouldReceive('embeddingModel->embedContent')
                ->andReturn((object) ['embedding' => (object) ['values' => [0.1, 0.2, 0.3]]]);
        });

        $response = $this->actingAs($this->user)->postJson('/api/v1/reconcile-embeddings', [
            'bank_statements' => [$statementFile],
            'ledgers' => [$ledgerFile],
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

        $response = $this->actingAs($user)->postJson('/api/v1/reconcile-embeddings', [
            'statement' => $invalidFile,
            'ledger' => $invalidFile,
        ]);

        $response->assertStatus(422);
    }
}
