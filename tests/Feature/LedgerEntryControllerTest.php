<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Ledger;
use App\Models\BookkeepingLedger;
use App\Models\AccountChart;
use App\Models\ChartAccountCategory;
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

        DB::table('account_chart_categories')->insert([
            'id' => '550e8400-e29b-41d4-a716-446655440002',
            'title' => 'Test Category',
            'description' => 'Test Category Description',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('user_chart_categories')->insert([
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
        $category = ChartAccountCategory::first();
        $data = [
            'bookkeeping_ledger_id' => $this->bookkeepingLedger->id,
            'transaction_type' => $category->title,
            'transaction_date' => '2024-01-15',
            'description' => 'Test transaction',
            'amount' => 1000.00,
            'paid_status' => 'paid',
            'due_date' => '2024-02-15',
            'amount_paid' => 1000.00,
            'bank_account_id' => '550e8400-e29b-41d4-a716-446655440001',
            'account_chart_id' => '550e8400-e29b-41d4-a716-446655440000',
            'reference' => 'INV-2024-001',
            'attachment' => UploadedFile::fake()->image('receipt.jpg')
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

    public function test_user_can_upload_valid_ledger_csv()
    {
        $user = User::factory()->create();
        $ledger = BookkeepingLedger::factory()->create();

        $file = UploadedFile::fake()->create('ledger.csv', 100, 'text/csv');

        $response = $this->actingAs($this->user)->postJson('/api/v1/ledger-entries/upload', [
            'ledger_file' => $file,
            'ledger' => $ledger->id,
            'transaction_type' => 'Expense',
            'mapper' => [
                'date' => 'Date',
                'description' => 'Description',
                'amount' => 'Amount'
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
        ]);
    }

    public function test_upload_fails_with_missing_file()
    {
        $user = User::factory()->create();
        $ledger = BookkeepingLedger::factory()->create();

        $response = $this->actingAs($this->user, 'api')->postJson('/api/v1/ledger-entries/upload', [
            'ledger' => $ledger->id,
            'transaction_type' => 'Expense',
            'mapper' => [
                'date' => 'Date',
                'description' => 'Description',
                'amount' => 'Amount'
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Validation failed'
        ]);
    }

    public function test_upload_fails_with_invalid_ledger_id()
    {
        $user = User::factory()->create();

        $file = UploadedFile::fake()->create('ledger.csv', 100, 'text/csv');

        $response = $this->actingAs($this->user, 'api')->postJson('/api/v1/ledger-entries/upload', [
            'ledger_file' => $file,
            'ledger' => 'invalid-uuid-1234',
            'transaction_type' => 'Expense',
            'mapper' => [
                'date' => 'Date',
                'description' => 'Description',
                'amount' => 'Amount'
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Validation failed'
        ]);
    }

    public function test_upload_fails_with_wrong_file_format()
    {
        $user = User::factory()->create();
        $ledger = BookkeepingLedger::factory()->create();

        $file = UploadedFile::fake()->create('ledger.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->user, 'api')->postJson('/api/v1/ledger-entries/upload', [
            'ledger_file' => $file,
            'ledger' => $ledger->id,
            'transaction_type' => 'Expense',
            'mapper' => [
                'date' => 'Date',
                'description' => 'Description',
                'amount' => 'Amount'
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Validation failed'
        ]);
    }

    public function test_unauthenticated_users_cannot_upload()
    {
        $ledger = BookkeepingLedger::factory()->create();
        $file = UploadedFile::fake()->create('ledger.csv', 100, 'text/csv');

        $response = $this->postJson('/api/v1/ledger-entries/upload', [
            'ledger_file' => $file,
            'ledger' => $ledger->id,
            'transaction_type' => 'Expense',
            'mapper' => [
                'date' => 'Date',
                'description' => 'Description',
                'amount' => 'Amount'
            ],
        ]);

        $response->assertStatus(401); // unauthorized
    }
}
