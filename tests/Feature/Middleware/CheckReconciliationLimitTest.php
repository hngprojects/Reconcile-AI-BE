<?php

namespace Tests\Feature\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\PaymentPlan;
use App\Models\Plan;
use App\Models\Reconciliation;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;

class CheckReconciliationLimitTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $planStarter;
    protected $planBusiness;

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

        // Mock file uploads
        $file1 = UploadedFile::fake()->create('file1.csv', 100);
        $file2 = UploadedFile::fake()->create('file2.csv', 100);

        $this->actingAs($this->user)
            ->postJson(route('reconcile'), [
                'file1'            => $file1,
                'file2'            => $file2,
                'reconcile_option' => 'reconcile_with_Gemini',
            ])
            ->assertStatus(200); // Allowed
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
        $file2 = UploadedFile::fake()->create('file2.csv', 100);

        $this->actingAs($this->user)
            ->postJson(route('reconcile'), [
                'file1'            => $file1,
                'file2'            => $file2,
                'reconcile_option' => 'reconcile_with_Gemini',
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

        // Simulate 4 reconciliations (limit is 5)
        Reconciliation::factory()->count(4)->create([
            'user_id' => $this->user->id,
            'created_at' => now()
        ]);

        // Mock file uploads
        $file1 = UploadedFile::fake()->create('file1.csv', 100);
        $file2 = UploadedFile::fake()->create('file2.csv', 100);

        $this->actingAs($this->user)
            ->postJson(route('reconcile'), [
                'file1'            => $file1,
                'file2'            => $file2,
                'reconcile_option' => 'reconcile_with_Gemini',
            ])
            ->assertStatus(200); // Allowed
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
        $file2 = UploadedFile::fake()->create('file2.csv', 100);

        $response = $this->actingAs($user)->postJson(route('reconcile'), [
            'file1'            => $file1,
            'file2'            => $file2,
            'reconcile_option' => 'reconcile_with_Gemini',
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

        // Mock file uploads
        $file1 = UploadedFile::fake()->create('file1.csv', 100);
        $file2 = UploadedFile::fake()->create('file2.csv', 100);

        // Make a successful reconciliation
        $this->actingAs($this->user)
            ->postJson(route('reconcile'), [
                'file1'            => $file1,
                'file2'            => $file2,
                'reconcile_option' => 'reconcile_with_Gemini',
            ])
            ->assertStatus(200);

        // Check if reconciliations_used is updated
        $this->assertEquals(1, $paymentPlan->fresh()->reconciliations_used);
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
        $file2 = UploadedFile::fake()->create('file2.csv', 100);

        $this->actingAs($this->user)
            ->postJson(route('reconcile'), [
                'file1'            => $file1,
                'file2'            => $file2,
                'reconcile_option' => 'reconcile_with_Gemini',
            ])
            ->assertStatus(403)
            ->assertJson(['message' => 'No active plan found. Please subscribe.']);
    }
}