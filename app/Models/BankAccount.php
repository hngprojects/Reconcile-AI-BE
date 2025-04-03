<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'bank_name',
        'account_number',
        'account_name',
        'is_default',
        'opening_balance',
        'currency',
    ];
}
