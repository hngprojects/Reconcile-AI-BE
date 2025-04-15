<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\LedgerEntry;
use App\Models\BankAccount;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class LedgerEntryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $bankAccount;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->bankAccount = BankAccount::create([
            'id' => Str::uuid(),
            'user_id' => $this->user->id,
            'name' => 'Test Bank Account',
            'account_number' => '1234567890',
            'bank_name' => 'Test Bank',
            'account_name' => 'Test Account Name',
            'currency' => 'USD',
            'opening_balance' => 0.00,
            'is_default' => true
        ]);
    }

    public function test_cannot_access_other_users_ledger_entry()
    {
        $this->actingAs($this->user);
        $otherUser = User::factory()->create();
        $otherBankAccount = BankAccount::create([
            'id' => Str::uuid(),
            'user_id' => $otherUser->id,
            'name' => 'Other Bank Account',
            'account_number' => '9876543210',
            'bank_name' => 'Other Bank',
            'account_name' => 'Other Test Account',
            'currency' => 'USD',
            'opening_balance' => 0.00,
            'is_default' => true
        ]);

        $otherLedgerEntry = LedgerEntry::factory()->create([
            'user_id' => $otherUser->id,
            'bank_account_id' => $otherBankAccount->id
        ]);

        $response = $this->getJson("/api/v1/ledger-entries/{$otherLedgerEntry->id}");

        $response->assertStatus(404);
    }

    public function test_can_update_ledger_entry()
    {
        $this->actingAs($this->user);
        $ledgerEntry = LedgerEntry::factory()->create([
            'user_id' => $this->user->id,
            'bank_account_id' => $this->bankAccount->id
        ]);

        $updateData = [
            'description' => 'Updated description',
            'amount' => 2000.00
        ];

        $response = $this->putJson("/api/v1/ledger-entries/{$ledgerEntry->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'status_code' => 200,
                'message' => 'Ledger entry updated successfully'
            ]);

        $this->assertDatabaseHas('ledger_entries', [
            'id' => $ledgerEntry->id,
            'description' => 'Updated description',
            'amount' => 2000.00
        ]);
    }

    public function test_validation_rules()
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/v1/ledger-entries', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'ledger_category',
                'transaction_type',
                'transaction_date',
                'description',
                'amount',
                'paid_status',
                'amount_paid',
                'bank_account_id'
            ]);
    }

    public function test_unauthenticated_access()
    {
        $response = $this->getJson('/api/v1/ledger-entries');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.'
            ]);
    }
}