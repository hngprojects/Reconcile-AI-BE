<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\Reconciliation;
use App\Models\User;
use App\Services\NewReconciliation\NewReconciliationService;
use Illuminate\Support\Facades\Log;

class ProcessDraftReconciliation implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels, Dispatchable;


    public function __construct(
        public Reconciliation $reconciliation,
        public User $user
    ) {}

    public function handle(NewReconciliationService $service)
    {
        try {
            // Validate ownership
            if ($this->reconciliation->user_id !== $this->user->id) {
                throw new \Exception('You do not own this reconciliation');
            }

            // Validate required data
            if ($this->reconciliation->ledgers()->count() === 0) {
                throw new \Exception('Reconciliation must have at least one ledger');
            }

            if ($this->reconciliation->statementFiles()->count() === 0) {
                throw new \Exception('Reconciliation must have at least one statement file');
            }

            $service->usingEmbeddings(
                [],
                [],
                $this->user,
                $this->reconciliation
            );
        } catch (\Exception $e) {
            Log::error('Reconciliation job failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
