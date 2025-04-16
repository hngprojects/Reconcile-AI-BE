<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LedgerPayment extends Model
{
    use HasUuids;
    protected $table = 'ledger_payments';
    protected $fillable = [
        'ledger_id',
        'payment_status',
        'due_date',
        'amount_paid',
        'bank_account_id',
        'account_chart_id',
        'reference',
        'attachment'
    ];

    public function ledger()
    {
        return $this->belongsTo(Ledger::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }
}
