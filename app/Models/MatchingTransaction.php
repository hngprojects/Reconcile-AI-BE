<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Models\Ledger;
use App\Models\Statement;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MatchingTransaction extends Pivot
{
    use HasFactory, HasUuids;
    protected $table = "matched_statements";
    protected $fillable = [
        'statement_id',
        'ledger_id',
        'user_id',
        'status'
    ];
    protected $keyType = 'string';
    public $incrementing = false;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ledger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class);
    }

    public function statement(): BelongsTo
    {
        return $this->belongsTo(Statement::class);
    }
}
