<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\BookkeepingLedger;
use App\Models\LedgerEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Illuminate\Support\Str;

class LedgerEntryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $ledger;

    protected function setUp(): void
    {
        parent::setUp();
        
        Storage::fake('public');
        
        $this->user = User::factory()->create();
        
        $this->ledger = BookkeepingLedger::create([
            'id' => Str::uuid(),
            'user_id' => $this->user->id,
            'name' => 'Test Ledger',
            'description' => 'Test Description',
            'categories' => ['Assets', 'Revenue'],
            'is_active' => true,
            'is_default' => true
        ]);
    }

    public function test_user_can_fetch_ledger_entries()
    {
        $this->actingAs($this->user);

        LedgerEntry::create([
            'id' => Str::uuid(),
            'ledger_id' => $this->ledger->id,
            'user_id' => $this->user->id,
            'account_category' => 'Cash',
            'transaction_type' => 'income',
            'date' => now(),
            'description' => 'Test Entry',
            'amount' => 1000
        ]);

        $response = $this->getJson("/api/v1/bookkeeping-ledgers/{$this->ledger->id}/entries");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status_code',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'ledger_id',
                        'user_id',
                        'account_category',
                        'transaction_type',
                        'date',
                        'description',
                        'amount'
                    ]
                ]
            ]);
    }

    public function test_user_can_create_ledger_entry()
    {
        $this->actingAs($this->user);

        $entryData = [
            'ledger_id' => $this->ledger->id,
            'account_category' => 'Cash',
            'transaction_type' => 'income',
            'date' => '2024-03-15',
            'description' => 'Test Entry',
            'amount' => 1000,
            'paid_status' => true,
            'attachment' => UploadedFile::fake()->create('document.pdf', 100)
        ];

        $response = $this->postJson('/api/v1/ledger-entries', $entryData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status_code',
                'message',
                'data' => [
                    'id',
                    'ledger_id',
                    'user_id',
                    'account_category',
                    'transaction_type',
                    'date',
                    'description',
                    'amount',
                    'attachment'
                ]
            ]);
    }
} 