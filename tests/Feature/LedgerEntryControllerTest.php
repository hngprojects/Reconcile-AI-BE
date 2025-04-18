<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Ledger;
use App\Models\BookkeepingLedger;
use App\Models\AccountChart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LedgerEntryControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $bookkeepingLedger;

    public function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->user = User::factory()->create();
        $this->bookkeepingLedger = BookkeepingLedger::factory()->create();

        DB::table('reconciliations')->insert([
            'id' => '550e8400-e29b-41d4-a716-446655440004',
            'user_id' => $this->user->id,
            'title' => 'Test Reconciliation',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('account_chart_categories')->insert([
            'id' => '550e8400-e29b-41d4-a716-446655440002',
            'title' => 'Test Category',
            'description' => 'Test Category Description',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('user_chart_categories')->insert([
            'id' => '550e8400-e29b-41d4-a716-446655440003',
            'user_id' => $this->user->id,
            'account_chart_category_id' => '550e8400-e29b-41d4-a716-446655440002',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('account_charts')->insert([
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'user_id' => $this->user->id,
            'account_chart_category_id' => '550e8400-e29b-41d4-a716-446655440002',
            'account_number' => 1000,
            'account_name' => 'Test Account',
            'description' => 'Test Description',
            'balance' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('bank_accounts')->insert([
            'id' => '550e8400-e29b-41d4-a716-446655440001',
            'user_id' => $this->user->id,
            'bank_name' => 'Test Bank',
            'account_number' => '1234567890',
            'account_name' => 'Test Account Name',
            'opening_balance' => 10000.00,
            'currency' => 'USD',
            'is_default' => false,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    public function test_can_create_ledger_entry()
    {
        $data = [
            'bookkeeping_ledger_id' => $this->bookkeepingLedger->id,
            'transaction_type' => 'Income',
            'transaction_date' => '2024-01-15',
            'description' => 'Test transaction',
            'amount' => 1000.00,
            'paid_status' => 'paid',
            'due_date' => '2024-02-15',
            'amount_paid' => 1000.00,
            'bank_account_id' => '550e8400-e29b-41d4-a716-446655440001',
            'account_chart_id' => '550e8400-e29b-41d4-a716-446655440000',
            'reference' => 'INV-2024-001',
            'attachment' => UploadedFile::fake()->image('receipt.jpg'),
            'reconciliation_id' => '550e8400-e29b-41d4-a716-446655440004'
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/ledger-entries', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status_code',
                'message',
                'data' => [
                    'ledger',
                    'payment'
                ]
            ]);

        $this->assertDatabaseHas('ledgers', [
            'bookkeeping_ledger_id' => $data['bookkeeping_ledger_id'],
            'transaction_type' => $data['transaction_type'],
            'date' => $data['transaction_date'],
            'person' => $data['description'],
            'amount' => $data['amount'],
        ]);
    }
}
