<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\NewReconciliation\NewReconciliationService;
use App\Repositories\Reconciliation\ReconciliationRepository;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\User;
use App\Models\Reconciliation;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessReconciliation implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels, Dispatchable;

    protected $statements;
    protected $ledgers;
    protected $mapper;
    protected $user;
    protected $reconciliation;
    public $tries = 3;
    public $timeout = 10800;
    protected $mainRepository;

    public function __construct(array $statements, array $ledgers, User $user, Reconciliation $reconciliation, ReconciliationRepository $mainRepository)
    {
        $this->mainRepository = $mainRepository;
        $this->statements = $statements;
        $this->ledgers = $ledgers;
        $this->user = $user;
        $this->reconciliation = $reconciliation;
    }


    public function handle(NewReconciliationService $service)
    {
        try {
            $service->usingEmbeddings($this->statements, $this->ledgers, $this->user, $this->reconciliation);
        } catch (Throwable $e) {
            Log::error("ProcessReconciliation Job Failed: " . $e->getMessage());
            $this->mainRepository->updateRecon($this->reconciliation, [
                ...$this->reconciliation->toArray(),
                'status' => 'failed',
            ]);
            $this->fail($e);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $e): void
    {
        Log::error("ProcessReconciliation Job Failed: " . $e->getMessage());
    }
}
