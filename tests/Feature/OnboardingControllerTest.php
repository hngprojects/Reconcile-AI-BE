<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\BankAccount;
use App\Models\BusinessInfo;
use App\Models\CompanyLedger;
use App\Models\OnboardingProgress;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OnboardingControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $this->user = User::factory()->create();
    }

    #[Test]
    public function it_can_save_business_details()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/onboarding/business', [
                'name' => 'Test Company',
                'type' => 'llc',
                'reporting_year' => 'January-December',
                'currency' => 'USD'
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'name',
                    'type',
                    'reporting_year',
                    'currency',
                    'user_id',
                    'created_at',
                    'updated_at'
                ]
            ]);

        $this->assertDatabaseHas('business_infos', [
            'name' => 'Test Company',
            'user_id' => $this->user->id
        ]);

        $this->assertDatabaseHas('onboarding_progress', [
            'user_id' => $this->user->id,
            'completed_basics' => true
        ]);
    }

    #[Test]
    public function it_validates_business_details_input()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/onboarding/business', [
                'name' => '', // Empty name should fail validation
                'type' => 'invalid_type',
                'currency' => 'USDD' // Too long
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'currency']);
    }

    #[Test]
    public function it_can_save_bank_account()
    {
        // First create a business
        $business = BusinessInfo::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/onboarding/bank', [
                'business_infos_id' => $business->id,
                'bank_name' => 'Test Bank',
                'account_name' => 'Business Account',
                'account_number' => '1234567890',
                'opening_balance' => 1000.50,
                'is_primary' => true
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'business_infos_id',
                    'bank_name',
                    'account_name',
                    'account_number',
                    'opening_balance',
                    'is_primary',
                    'created_at',
                    'updated_at'
                ]
            ]);

        $this->assertDatabaseHas('bank_accounts', [
            'business_infos_id' => $business->id,
            'bank_name' => 'Test Bank',
            'is_primary' => true
        ]);

        $this->assertDatabaseHas('onboarding_progress', [
            'user_id' => $this->user->id,
            'completed_bank' => true
        ]);
    }

    #[Test]
    public function it_validates_bank_account_input()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/onboarding/bank', [
                'business_infos_id' => "d37ff7f3-d93b-478b-8663-5fcd5ce0a3f6", // Non-existent ID
                'opening_balance' => 'not-a-number'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['business_infos_id', 'opening_balance', 'bank_name', 'account_name', 'account_number']);
    }

    #[Test]
    public function it_sets_one_bank_account_as_primary()
    {
        $business = BusinessInfo::factory()->create([
            'user_id' => $this->user->id
        ]);

        // Create first bank account as primary
        $this->actingAs($this->user)
            ->postJson('/api/v1/onboarding/bank', [
                'business_infos_id' => $business->id,
                'bank_name' => 'First Bank',
                'account_name' => 'Primary Account',
                'account_number' => '1111111111',
                'opening_balance' => 1000,
                'is_primary' => true
            ]);

        // Create second bank account as primary
        $this->actingAs($this->user)
            ->postJson('/api/v1/onboarding/bank', [
                'business_infos_id' => $business->id,
                'bank_name' => 'Second Bank',
                'account_name' => 'Also Primary Account',
                'account_number' => '2222222222',
                'opening_balance' => 2000,
                'is_primary' => true
            ]);

        // First account should no longer be primary
        $this->assertDatabaseHas('bank_accounts', [
            'bank_name' => 'First Bank',
            'is_primary' => false
        ]);

        // Second account should be primary
        $this->assertDatabaseHas('bank_accounts', [
            'bank_name' => 'Second Bank',
            'is_primary' => true
        ]);
    }

    #[Test]
    public function it_can_setup_ledger()
    {
        $business = BusinessInfo::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/onboarding/ledger', [
                'business_infos_id' => $business->id,
                'type' => 'general',
                'is_active' => true
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'business_infos_id',
                    'type',
                    'is_active',
                    'created_at',
                    'updated_at'
                ]
            ]);

        $this->assertDatabaseHas('company_ledgers', [
            'business_infos_id' => $business->id,
            'type' => 'general',
            'is_active' => true
        ]);

        $this->assertDatabaseHas('onboarding_progress', [
            'user_id' => $this->user->id,
            'completed_ledger' => true
        ]);
    }

    #[Test]
    public function it_validates_ledger_input()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/onboarding/ledger', [
                'business_infos_id' => "d37ff7f3-d93b-478b-8663-5fcd5ce0a3f6", // Non-existent ID
                'type' => 'invalid_type'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['business_infos_id', 'type']);
    }

    #[Test]
    public function it_sets_one_ledger_as_active()
    {
        $business = BusinessInfo::factory()->create([
            'user_id' => $this->user->id
        ]);

        // Create first ledger as active
        $this->actingAs($this->user)
            ->postJson('/api/v1/onboarding/ledger', [
                'business_infos_id' => $business->id,
                'type' => 'general',
                'is_active' => true
            ]);

        // Create second ledger as active
        $this->actingAs($this->user)
            ->postJson('/api/v1/onboarding/ledger', [
                'business_infos_id' => $business->id,
                'type' => 'vendor',
                'is_active' => true
            ]);

        // First ledger should no longer be active
        $this->assertDatabaseHas('company_ledgers', [
            'type' => 'general',
            'is_active' => false
        ]);

        // Second ledger should be active
        $this->assertDatabaseHas('company_ledgers', [
            'type' => 'vendor',
            'is_active' => true
        ]);
    }

    #[Test]
    public function it_can_complete_onboarding()
    {
        OnboardingProgress::factory()->create([
            'user_id' => $this->user->id,
            'completed_basics' => true,
            'completed_bank' => true,
            'completed_ledger' => true,
            'completed_finish' => false
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/onboarding/complete');

        $response->assertStatus(200);

        $this->assertDatabaseHas('onboarding_progress', [
            'user_id' => $this->user->id,
            'completed_finish' => true
        ]);
    }

    #[Test]
    public function it_can_get_onboarding_status()
    {
        // Create test data
        $business = BusinessInfo::factory()->create([
            'user_id' => $this->user->id
        ]);

        $bankAccount = BankAccount::factory()->create([
            'business_infos_id' => $business->id
        ]);

        $ledger = CompanyLedger::factory()->create([
            'business_infos_id' => $business->id
        ]);

        $progress = OnboardingProgress::factory()->create([
            'user_id' => $this->user->id,
            'completed_basics' => true,
            'completed_bank' => true,
            'completed_ledger' => false,
            'completed_finish' => false
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/onboarding/status');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'progress',
                    'business',
                    'bank_accounts',
                    'ledgers'
                ]
            ]);

        // Verify the correct data is returned
        $this->assertEquals($business->id, $response->json('data.business.id'));
        $this->assertEquals($bankAccount->id, $response->json('data.bank_accounts.0.id'));
        $this->assertEquals($ledger->id, $response->json('data.ledgers.0.id'));
        $this->assertEquals(true, $response->json('data.progress.completed_basics'));
        $this->assertEquals(false, $response->json('data.progress.completed_finish'));
    }

    #[Test]
    public function it_checks_complete_onboarding_state()
    {
        // Create a progress record with all steps completed
        $progress = OnboardingProgress::factory()->create([
            'user_id' => $this->user->id,
            'completed_basics' => true,
            'completed_bank' => true,
            'completed_ledger' => true,
            'completed_finish' => true
        ]);

        // Create a service instance to test the isComplete method
        $progressRepo = app(\App\Repositories\OnboardingProgress\OnboardingProgressRepository::class);
        
        $isComplete = $progressRepo->isComplete($this->user->id);
        
        $this->assertTrue($isComplete);
        
        // Now set one step to incomplete
        $progress->update(['completed_ledger' => false]);
        
        $isComplete = $progressRepo->isComplete($this->user->id);
        
        $this->assertFalse($isComplete);
    }

}
