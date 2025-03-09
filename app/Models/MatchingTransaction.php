<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Models\Guest;
use App\Models\Ledger;
use App\Models\Statement;

class MatchingTransaction extends Pivot
{
    protected $table = "matched_ledgers_and_statements";
    protected $fillable = [
        'statement_id',
        'ledger_id',
        'guest_id',
        'user_id',
        'status'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function ledgers(): BelongsTo
    {
        return $this->hasMany(Ledger::class);
    }

    public function statements(): BelongsTo
    {
        return $this->hasMany(Statement::class);
    }
}
