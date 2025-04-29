<?php

namespace Tests\Feature\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\PaymentPlan;
use App\Models\Plan;
use App\Models\Reconciliation;
use App\Models\BookkeepingLedger;
use App\Models\BankAccount;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use Mockery;
use App\Services\NewReconciliationService;
use App\Repositories\Reconciliation\ReconciliationRepository;
use App\Repositories\UserFile\UserFileRepository;

class CheckReconciliationLimitTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $planStarter;
    protected $planBusiness;
    protected $mockService;
    protected $reconciliationRepository;
    protected $userFileRepository;

    public function setUp(): void
    {
        parent::setUp();

        // Create a user
        $this->user = User::factory()->create();

        // Create plans
        $this->planStarter = Plan::factory()->create([
            'plan' => 'Starter',
            'reconciliations_per_month' => 5
        ]);

        $this->planBusiness = Plan::factory()->create([
            'plan' => 'Business',
            'reconciliations_per_month' => -1 // Unlimited
        ]);

        // Mock the reconciliation service
        $this->mockService = Mockery::mock(NewReconciliationService::class);
        $this->app->instance(NewReconciliationService::class, $this->mockService);
        $this->reconciliationRepository = Mockery::mock(ReconciliationRepository::class);
        $this->userFileRepository = Mockery::mock(UserFileRepository::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_allows_business_plan_users_to_reconcile_unlimited()
    {
        $this->actingAs($this->user);
        $paymentPlan = PaymentPlan::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->planBusiness->id,
            'start_date' => now(),
            'expire_date' => now()->addMonth(30),
            'is_active' => true
        ]);

        $this->reconciliationRepository
            ->shouldReceive('store')
            ->andReturn(new Reconciliation());

        $this->userFileRepository
            ->shouldReceive('store')
            ->andReturnUsing(function ($data) {
                return (object) ['file_name' => $data['file_name'], 'type' => $data['type']];
            });

        // Mock file uploads
        $file1 = UploadedFile::fake()->create('file1.csv', 100);
        $ledgerType  = BookkeepingLedger::factory()->create();
        $acc  = BankAccount::factory()->create();

        $response = $this->actingAs($this->user)
            ->postJson(route('reconcile'), [
                'bank_statements' => [
                    [
                        'file' => $file1,
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
                'ledgers' => [$ledgerType->id],
                'title' => 'Reconciliation Test'
            ])
            ->assertStatus(200);
    }

    #[Test]
    public function it_blocks_users_when_reconciliation_limit_is_reached()
    {
        $paymentPlan = PaymentPlan::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->planStarter->id,
            'start_date' => now(),
            'expire_date' => now()->addMonth(),
            'is_active' => true
        ]);

        // Simulate reaching reconciliation limit
        Reconciliation::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'created_at' => now()
        ]);

        // Mock file uploads
        $file1 = UploadedFile::fake()->create('file1.csv', 100);
        $ledgerType  = BookkeepingLedger::factory()->create();
        $acc  = BankAccount::factory()->create();

        $response = $this->actingAs($this->user)
            ->postJson(route('reconcile'), [
                'bank_statements' => [
                    [
                        'file' => $file1,
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
                'ledgers' => [$ledgerType->id],
                'title' => 'Reconciliation Test'
            ])
            ->assertStatus(429)
            ->assertJson(['message' => 'You have reached your reconciliation limit. Please upgrade your plan or wait until the next period.']);
    }

    #[Test]
    public function it_allows_reconciliation_if_under_limit()
    {
        $this->actingAs($this->user);
        $paymentPlan = PaymentPlan::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->planStarter->id,
            'start_date' => now(),
            'expire_date' => now()->addMonth(),
            'is_active' => true
        ]);

        // Setup mock expectations
        $this->reconciliationRepository
            ->shouldReceive('store')
            ->andReturn(new Reconciliation());

        $this->userFileRepository
            ->shouldReceive('store')
            ->andReturnUsing(function ($data) {
                return (object) ['file_name' => $data['file_name'], 'type' => $data['type']];
            });

        // Simulate 4 reconciliations (limit is 5)
        Reconciliation::factory()->count(4)->create([
            'user_id' => $this->user->id,
            'created_at' => now()
        ]);

        // Mock file uploads
        $file1 = UploadedFile::fake()->create('file1.csv', 100);
        $ledgerType  = BookkeepingLedger::factory()->create();
        $acc  = BankAccount::factory()->create();

        $this->actingAs($this->user)
            ->postJson(route('reconcile'), [
                'bank_statements' => [
                    [
                        'file' => $file1,
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
                'ledgers' => [$ledgerType->id],
                'title' => 'Reconciliation Test'
            ])
            ->assertStatus(200);
    }

    public function test_user_cannot_exceed_reconciliation_limit()
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create(['plan' => 'Basic', 'reconciliations_per_month' => 5]);
        $paymentPlan = PaymentPlan::factory()->create(['user_id' => $user->id, 'plan_id' => $plan->id]);

        // Simulate reconciliations exceeding the limit
        Reconciliation::factory()->count(5)->create(['user_id' => $user->id]);

        // Mock file uploads
        $file1 = UploadedFile::fake()->create('file1.csv', 100);
        $ledgerType  = BookkeepingLedger::factory()->create();
        $acc  = BankAccount::factory()->create();

        $response = $this->actingAs($user)->postJson(route('reconcile'), [
            'bank_statements' => [
                [
                    'file' => $file1,
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
            'ledgers' => [$ledgerType->id],
            'title' => 'Reconciliation Test'
        ]);

        $response->assertStatus(429)
            ->assertJson(['message' => 'You have reached your reconciliation limit. Please upgrade your plan or wait until the next period.']);
    }

    #[Test]
    public function it_correctly_updates_reconciliations_used()
    {
        $this->actingAs($this->user);
        $paymentPlan = PaymentPlan::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->planStarter->id,
            'start_date' => now(),
            'expire_date' => now()->addMonth(),
            'is_active' => true
        ]);

        // Setup mock expectations
        $this->reconciliationRepository
            ->shouldReceive('store')
            ->andReturn(new Reconciliation());

        $this->userFileRepository
            ->shouldReceive('store')
            ->andReturnUsing(function ($data) {
                return (object) ['file_name' => $data['file_name'], 'type' => $data['type']];
            });

        // Mock file uploads
        $file1 = UploadedFile::fake()->create('file1.csv', 100);
        $ledgerType  = BookkeepingLedger::factory()->create();
        $acc  = BankAccount::factory()->create();

        $response = $this->actingAs($this->user)
            ->postJson(route('reconcile'), [
                'bank_statements' => [
                    [
                        'file' => $file1,
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
                'ledgers' => [$ledgerType->id],
                'title' => 'Reconciliation Test'
            ])
            ->assertStatus(200);
    }

    #[Test]
    public function it_denies_access_if_user_has_no_active_plan()
    {
        PaymentPlan::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->planStarter->id,
            'start_date' => now(),
            'expire_date' => now()->addMonth(),
            'is_active' => false
        ]);

        // Mock file uploads
        $file1 = UploadedFile::fake()->create('file1.csv', 100);
        $ledgerType  = BookkeepingLedger::factory()->create();
        $acc  = BankAccount::factory()->create();

        $this->actingAs($this->user)
            ->postJson(route('reconcile'), [
                'bank_statements' => [
                    [
                        'file' => $file1,
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
                'ledgers' => [$ledgerType->id],
                'title' => 'Reconciliation Test'
            ])
            ->assertStatus(403)
            ->assertJson(['message' => 'No active plan found. Please subscribe.']);
    }
}
