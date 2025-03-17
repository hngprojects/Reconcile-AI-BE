<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\NewReconciliation\NewReconciliationService;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\User;

class ProcessReconciliation implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels, Dispatchable;

    protected $statement;
    protected $ledger;
    protected $user;

    public function __construct(string $statement, string $ledger, User $user)
    {
        $this->statement = $statement;
        $this->ledger = $ledger;
        $this->user = $user;
    }

    public function handle(NewReconciliationService $service)
    {
        try{
            $service->usingEmbeddings($this->statement, $this->ledger, $this->user);
        }catch(Throwable $e){
            Log::error("ProcessReconciliation Job Failed: " . $e->getMessage());
            $this->fail($e);
        }
    }
}
