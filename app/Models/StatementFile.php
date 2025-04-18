<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class StatementFile extends Model
{
    use HasUuids;

    protected $fillable = [
        'bank_account_id',
        'period',
        'user_file_id'
    ];

    public function file(): BelongsTo
    {
        return $this->belongsTo(UserFile::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function reconciliations()
    {
        return $this->belongsToMany(Reconciliation::class, 'reconciliation_statement_files');
    }
}
