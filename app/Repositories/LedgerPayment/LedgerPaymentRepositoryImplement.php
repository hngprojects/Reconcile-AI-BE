<?php

namespace App\Repositories\LedgerPayment;

use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\LedgerPayment;
use App\Models\Ledger;

class LedgerPaymentRepositoryImplement extends Eloquent implements LedgerPaymentRepository
{

    /**
     * Model class to be used in this repository for the common methods inside Eloquent
     * Don't remove or change $this->model variable name
     * @property Model|mixed $model;
     */
    protected LedgerPayment $model;

    public function __construct(LedgerPayment $model)
    {
        $this->model = $model;
    }

    public function findByLedger(Ledger $ledger)
    {
        return LedgerPayment::with('account')->where('ledger_id', '=', $ledger->id)->first();
    }
}
