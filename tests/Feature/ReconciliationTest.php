<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ReconciledRecord;
use App\Models\Reconciliation;
use App\Models\Statement;
use App\Models\Ledger;
use App\Services\ReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;
use Illuminate\Support\Str;
use App\Repositories\Statement\StatementRepository;
use App\Repositories\Ledger\LedgerRepository;
use App\Repositories\MatchingTransaction\MatchingTransactionRepository;
use App\Http\Resources\TransactionResource;

class ReconciliationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

}
