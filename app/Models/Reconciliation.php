<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class Reconciliation extends Model
{
    protected $fillable = [
        'user_id',
        'ledger_file',
        'statement_file'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
