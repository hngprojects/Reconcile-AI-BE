<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\NewReconciliation\NewReconciliationService;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\User;
use App\Models\Reconciliation;
use Illuminate\Support\Facades\Log;

class ProcessReconciliation implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels, Dispatchable;

    protected $statements;
    protected $ledgers;
    protected $user;
    protected $reconciliation;
    public $tries = 3;
    public $timeout = 10800;

    public function __construct(array $statements, array $ledgers, array $mapper, User $user, Reconciliation $reconciliation)
    {
        $this->statements = $statements;
        $this->ledgers = $ledgers;
        $this->mapper = $mapper;
        $this->user = $user;
        $this->reconciliation = $reconciliation;
    }

    public function handle(NewReconciliationService $service)
    {
        try{
            $service->usingEmbeddings($this->statements, $this->ledgers, $this->mapper, $this->user, $this->reconciliation);
        }catch(Throwable $e){
            \Log::error("ProcessReconciliation Job Failed: " . $e->getMessage());
            $this->fail($e);
        }
    }
}
