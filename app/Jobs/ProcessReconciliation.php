<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\NewReconciliation\NewReconciliationService;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ProcessReconciliation implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels, Dispatchable;

    protected $statements;
    protected $ledgers;
    protected $user;
    public $tries = 3;
    public $timeout = 10800;

    public function __construct(array $statements, array $ledgers, User $user)
    {
        $this->statements = $statements;
        $this->ledgers = $ledgers;
        $this->user = $user;
    }

    public function handle(NewReconciliationService $service)
    {
        try{
            $service->usingEmbeddings($this->statements, $this->ledgers, $this->user);
        }catch(Throwable $e){
            \Log::error("ProcessReconciliation Job Failed: " . $e->getMessage());
            $this->fail($e);
        }
    }
}
