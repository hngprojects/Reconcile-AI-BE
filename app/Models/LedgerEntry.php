<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LedgerEntry extends Model
{
    use HasFactory;
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'id', 'ledger_id', 'user_id', 'account_category', 'transaction_type', 'date', 
        'description', 'amount', 'paid_status', 'bank_account_id', 'reconciled', 
        'bank_ref', 'invoice_or_ref_number', 'attachment', 'notes'
    ];
    protected $casts = [
        'id' => 'string',
        'amount' => 'decimal:2',
        'paid_status' => 'boolean',
        'reconciled' => 'boolean',
        'date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function ledger()
    {
        return $this->belongsTo(BookkeepingLedger::class, 'ledger_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }
}