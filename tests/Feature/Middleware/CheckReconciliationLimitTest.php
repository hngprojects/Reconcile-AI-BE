<?php

namespace Tests\Feature\Middleware;

use Tests\TestCase;
use App\Models\User;
use App\Models\Plan;
use App\Models\PaymentPlan;
use App\Models\Reconciliation;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class CheckReconciliationLimitTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Plan $planStarter;
    protected Plan $planBusiness;
    protected array $requestData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        
        $this->planStarter = Plan::factory()->create([
            'plan' => 'Starter',
            'reconciliations_per_month' => 5
        ]);

        $this->planBusiness = Plan::factory()->create([
            'plan' => 'Business',
            'reconciliations_per_month' => -1
        ]);

        // Create mock files once to reuse
        $this->requestData = [
            'file1' => UploadedFile::fake()->create('file.csv', 100),
            'file2' => UploadedFile::fake()->create('file.csv', 100),
            'reconcile_option' => 'reconcile_with_Gemini'
        ];
    }

    #[Test]
    public function business_plan_users_have_unlimited_reconciliations()
    {
        PaymentPlan::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->planBusiness->id,
            'is_active' => true
        ]);

        $this->actingAs($this->user)
            ->postJson(route('reconcile'), $this->requestData)
            ->assertOk();
    }

    #[Test]
    public function blocks_users_when_reconciliation_limit_reached()
    {
        $paymentPlan = PaymentPlan::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->planStarter->id,
            'is_active' => true
        ]);

        Reconciliation::factory()
            ->count($this->planStarter->reconciliations_per_month)
            ->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->postJson(route('reconcile'), $this->requestData)
            ->assertStatus(429)
            ->assertJson(['message' => 'You have reached your reconciliation limit. Please upgrade your plan or wait until the next period.']);
    }

    #[Test]
    public function allows_reconciliation_when_under_limit()
    {
        PaymentPlan::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->planStarter->id,
            'is_active' => true
        ]);

        Reconciliation::factory()
            ->count($this->planStarter->reconciliations_per_month - 1)
            ->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->postJson(route('reconcile'), $this->requestData)
            ->assertOk();
    }

    #[Test]
    public function updates_reconciliations_used_count()
    {
        $paymentPlan = PaymentPlan::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->planStarter->id,
            'is_active' => true
        ]);

        $this->actingAs($this->user)
            ->postJson(route('reconcile'), $this->requestData)
            ->assertOk();

        $this->assertEquals(1, $paymentPlan->fresh()->reconciliations_used);
    }

    #[Test]
    public function denies_access_without_active_plan()
    {
        PaymentPlan::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->planStarter->id,
            'is_active' => false
        ]);

        $this->actingAs($this->user)
            ->postJson(route('reconcile'), $this->requestData)
            ->assertStatus(403)
            ->assertJson(['message' => 'No active plan found. Please subscribe.']);
    }
}