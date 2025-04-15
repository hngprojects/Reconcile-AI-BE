<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LedgerEntry extends Model
{
    use HasFactory;
    
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 
        'user_id',
        'ledger_category',
        'transaction_type',
        'transaction_date',
        'description',
        'amount',
        'paid_status',
        'due_date',
        'amount_paid',
        'bank_account_id',
        'account_category',
        'reference',
        'attachment'
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'due_date' => 'datetime',
        'amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }
}