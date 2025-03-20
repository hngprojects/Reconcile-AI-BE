<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\NewReconciliation\NewReconciliationServiceImplement;
use App\Repositories\Reconciliation\ReconciliationRepository;
use App\Repositories\UserFile\UserFileRepository;
use App\Repositories\Ledger\LedgerRepository;
use App\Repositories\Statement\StatementRepository;
use App\Repositories\MatchingTransaction\MatchingTransactionRepository;
use App\Models\Reconciliation;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Mockery;

class EmbeddingsTest extends TestCase
{
    protected $service;
    protected $reconciliationRepository;
    protected $userFileRepository;
    protected $ledgerRepository;
    protected $statementRepository;
    protected $matchedRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reconciliationRepository = Mockery::mock(ReconciliationRepository::class);
        $this->userFileRepository = Mockery::mock(UserFileRepository::class);
        $this->ledgerRepository = Mockery::mock(LedgerRepository::class);
        $this->statementRepository = Mockery::mock(StatementRepository::class);
        $this->matchedRepository = Mockery::mock(MatchingTransactionRepository::class);

        $this->service = new NewReconciliationServiceImplement(
            $this->reconciliationRepository,
            $this->userFileRepository,
            $this->ledgerRepository,
            $this->statementRepository,
            $this->matchedRepository
        );

        Storage::fake('local');
    }

    public function test_reconcile_embeddings_successful()
    {
        $statementFile = UploadedFile::fake()->create('statement.csv');
        $ledgerFile = UploadedFile::fake()->create('ledger.csv');
        $user = User::factory()->create();

        $this->reconciliationRepository
            ->shouldReceive('store')
            ->andReturn(new Reconciliation(['id' => 1]));

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

        $response = $this->actingAs($user)->postJson('/api/v1/reconcile-embeddings', [
            'bank_statements' => [$statementFile],
            'ledgers' => [$ledgerFile],
        ]);

        /*$response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'status_code',
                'message',
                'data'
            ]);*/
    }

    public function test_reconcile_embeddings_invalid_file()
    {
        $invalidFile = UploadedFile::fake()->create('invalid.txt');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/reconcile-embeddings', [
            'statement' => $invalidFile,
            'ledger' => $invalidFile,
        ]);

        $response->assertStatus(422);
    }
}
