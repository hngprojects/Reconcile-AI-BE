<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;

class AccountSetupTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    #[Test]
    public function it_creates_account_with_all_ledger_types()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/account/setup', [
            'business_name' => 'Test Business',
            'business_type' => 'Retail',
            'currency' => 'NGN',
            'reporting_year' => 'January - December',
            'bank_name' => 'Test Bank',
            'account_name' => 'Test Account',
            'account_number' => '1234567890',
            'opening_balance' => 50000,
            'ledger_types' => ['general', 'vendor', 'customer']
        ]);

        $response->assertStatus(201)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'business_info' => [
                    'id',
                    'user_id',
                    'name',
                    'type',
                    'reporting_year',
                    'currency',
                    'created_at',
                    'updated_at'
                ],
                'bank_account' => [
                    'id',
                    'user_id',
                    'bank_name',
                    'account_name',
                    'account_number',
                    'opening_balance',
                    'currency',
                    'is_default',
                    'created_at',
                    'updated_at'
                ],
                'ledgers' => [
                    '*' => [
                        'id',
                        'user_id',
                        'name',
                        'description',
                        'categories',
                        'is_default',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]
        ])
        ->assertJsonCount(3, 'data.ledgers')
        ->assertJsonPath('data.ledgers.0.name', 'General Ledger')
        ->assertJsonPath('data.ledgers.1.name', 'Vendor Ledger')
        ->assertJsonPath('data.ledgers.2.name', 'Customer Ledger');
    }

    #[Test]
    public function it_creates_account_with_only_general_ledger()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/account/setup', [
            'business_name' => 'Test Business',
            'business_type' => 'Retail',
            'currency' => 'NGN',
            'reporting_year' => 'January - December',
            'bank_name' => 'Test Bank',
            'account_name' => 'Test Account',
            'account_number' => '1234567890',
            'opening_balance' => 50000,
            'ledger_types' => ['general']
        ]);

        $response->assertStatus(201)
            ->assertJsonCount(1, 'data.ledgers')
            ->assertJsonPath('data.ledgers.0.name', 'General Ledger')
            ->assertJsonPath('data.ledgers.0.is_default', true);
    }

    #[Test]
    public function it_requires_all_business_fields()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/account/setup', [
            // Missing required fields
            'bank_name' => 'Test Bank',
            'account_name' => 'Test Account',
            'account_number' => '1234567890',
            'opening_balance' => 50000,
            'ledger_types' => ['general']
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'business_name', 'business_type', 'currency', 'reporting_year'
            ]);
    }

    #[Test]
    public function it_requires_at_least_one_ledger_type()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/account/setup', [
            'business_name' => 'Test Business',
            'business_type' => 'Retail',
            'currency' => 'NGN',
            'reporting_year' => 'January - December',
            'bank_name' => 'Test Bank',
            'account_name' => 'Test Account',
            'account_number' => '1234567890',
            'opening_balance' => 50000,
            'ledger_types' => [] // Empty array
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ledger_types']);
    }

    #[Test]
    public function it_validates_ledger_types_values()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/account/setup', [
            'business_name' => 'Test Business',
            'business_type' => 'Retail',
            'currency' => 'NGN',
            'reporting_year' => 'January - December',
            'bank_name' => 'Test Bank',
            'account_name' => 'Test Account',
            'account_number' => '1234567890',
            'opening_balance' => 50000,
            'ledger_types' => ['invalid'] // Invalid ledger type
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ledger_types.0']);
    }

    #[Test]
    public function it_sets_general_ledger_as_default_when_only_one_exists()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/account/setup', [
            'business_name' => 'Test Business',
            'business_type' => 'Retail',
            'currency' => 'NGN',
            'reporting_year' => 'January - December',
            'bank_name' => 'Test Bank',
            'account_name' => 'Test Account',
            'account_number' => '1234567890',
            'opening_balance' => 50000,
            'ledger_types' => ['general']
        ]);

        $response->assertJsonPath('data.ledgers.0.is_default', true);
    }

    /* #[Test]
    public function it_handles_database_errors_gracefully()
    {
        // Force database error by making account_number non-unique
        $existing = $this->user->bankAccounts()->create([
            'bank_name' => 'Existing',
            'account_name' => 'Existing',
            'account_number' => '1234567890',
            'currency' => 'NGN',
            'opening_balance' => 0
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/v1/account/setup', [
            'business_name' => 'Test Business',
            'business_type' => 'Retail',
            'currency' => 'NGN',
            'reporting_year' => 'January - December',
            'bank_name' => 'Test Bank',
            'account_name' => 'Test Account',
            'account_number' => '1234567890', // Duplicate
            'opening_balance' => 50000,
            'ledger_types' => ['general']
        ]);

        $response->assertStatus(422)
        ->assertJson([
            'message' => 'The account number has already been taken.',
            'errors' => [
                'account_number' => [
                    'The account number has already been taken.'
                ]
            ]
        ]);
    } */
}
