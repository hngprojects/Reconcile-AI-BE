<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\BookkeepingLedger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Str;

class BookkeepingLedgerControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
    }

    public function test_user_can_fetch_ledgers()
    {
        $this->actingAs($this->user);

        BookkeepingLedger::create([
            'id' => Str::uuid(),
            'user_id' => $this->user->id,
            'name' => 'Test Ledger',
            'description' => 'Test Description',
            'categories' => ['Assets', 'Revenue'],
            'is_active' => true,
            'is_default' => false
        ]);

        $response = $this->getJson('/api/v1/bookkeeping-ledgers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status_code',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'name',
                        'description',
                        'categories',
                        'is_active',
                        'is_default'
                    ]
                ]
            ]);
    }

    public function test_user_gets_default_ledger_when_none_exists()
    {
        $this->actingAs($this->user);

        $response = $this->getJson('/api/v1/bookkeeping-ledgers');

        $response->assertStatus(200);

        $this->assertDatabaseHas('bookkeeping_ledgers', [
            'user_id' => $this->user->id,
            'name' => 'General Ledger',
            'is_default' => true
        ]);
    }

    public function test_user_can_create_ledger()
    {
        $this->actingAs($this->user);

        $ledgerData = [
            'name' => 'Test Ledger',
            'description' => 'Test Description',
            'categories' => ['Assets', 'Revenue']
        ];

        $response = $this->postJson('/api/v1/bookkeeping-ledgers', $ledgerData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status_code',
                'message',
                'data' => [
                    'id',
                    'user_id',
                    'name',
                    'description',
                    'categories'
                ]
            ]);
    }

    public function test_user_can_toggle_ledger_status()
    {
        $this->actingAs($this->user);

        $ledger = BookkeepingLedger::create([
            'id' => Str::uuid(),
            'user_id' => $this->user->id,
            'name' => 'Test Ledger',
            'description' => 'Test Description',
            'categories' => ['Assets', 'Revenue'],
            'is_active' => true,
            'is_default' => false
        ]);

        $response = $this->putJson("/api/v1/bookkeeping-ledgers/{$ledger->id}/toggle", [
            'is_active' => false
        ]);

        $response->assertStatus(200);
        
        $ledger->refresh();
        $this->assertFalse($ledger->is_active);
    }
} 