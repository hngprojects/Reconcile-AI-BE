<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\BankAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Str;

class BankAccountControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
    }

    public function test_user_can_fetch_bank_accounts()
    {
        $this->actingAs($this->user);

        BankAccount::create([
            'id' => Str::uuid(),
            'user_id' => $this->user->id,
            'account_name' => 'Test Account',
            'account_number' => '1234567890',
            'bank_name' => 'Test Bank',
            'opening_balance' => 1000,
            'currency' => 'USD',
            'is_default' => false
        ]);

        $response = $this->getJson('/api/v1/bank-accounts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status_code',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'account_name',
                        'account_number',
                        'bank_name',
                        'opening_balance',
                        'currency',
                        'is_default'
                    ]
                ]
            ]);
    }

    public function test_user_can_create_bank_account()
    {
        $this->actingAs($this->user);

        $bankAccountData = [
            'account_name' => 'Test Account',
            'account_number' => '1234567890',
            'bank_name' => 'Test Bank',
            'opening_balance' => 1000,
            'currency' => 'USD',
            'is_default' => true
        ];

        $response = $this->postJson('/api/v1/bank-accounts', $bankAccountData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status_code',
                'message',
                'data' => [
                    'id',
                    'user_id',
                    'account_name',
                    'account_number',
                    'bank_name',
                    'opening_balance',
                    'currency',
                    'is_default'
                ]
            ]);
    }

    public function test_user_can_set_default_bank_account()
    {
        $this->actingAs($this->user);

        $firstAccount = BankAccount::create([
            'id' => Str::uuid(),
            'user_id' => $this->user->id,
            'account_name' => 'First Account',
            'account_number' => '1234567890',
            'bank_name' => 'Test Bank',
            'opening_balance' => 1000,
            'currency' => 'USD',
            'is_default' => true
        ]);

        $secondAccount = BankAccount::create([
            'id' => Str::uuid(),
            'user_id' => $this->user->id,
            'account_name' => 'Second Account',
            'account_number' => '0987654321',
            'bank_name' => 'Test Bank',
            'opening_balance' => 2000,
            'currency' => 'USD',
            'is_default' => false
        ]);

        $response = $this->putJson("/api/v1/bank-accounts/{$secondAccount->id}/default");

        $response->assertStatus(200)
            ->assertJson([
                'status_code' => 200,
                'message' => 'Account set as default successfully.'
            ]);

        $firstAccount->refresh();
        $secondAccount->refresh();

        $this->assertFalse($firstAccount->is_default, 'First account should no longer be default');

        $this->assertTrue($secondAccount->is_default, 'Second account should be default');

        $defaultCount = BankAccount::where('user_id', $this->user->id)
            ->where('is_default', true)
            ->count();
        $this->assertEquals(1, $defaultCount, 'There should be exactly one default account');
    }
} 