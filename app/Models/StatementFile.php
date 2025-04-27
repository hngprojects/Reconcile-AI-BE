<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class StatementFile extends Model
{
    use HasUuids;

    protected $fillable = [
        'bank_account_id',
        'start_date',
        'end_date',
        'user_file_id'
    ];

    public function userFile()
    {
        return $this->belongsTo(UserFile::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function reconciliations()
    {
        return $this->belongsToMany(Reconciliation::class, 'reconciliation_statement_files');
    }
}
