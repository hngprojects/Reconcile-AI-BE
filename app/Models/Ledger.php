<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relation\BelongsTo;
use App\Models\MatchedTransaction;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ledger extends Model
{
    /** @use HasFactory<\Database\Factories\LedgerFactory> */
    use HasFactory;

    protected $fillable = [
        'date',
        'description',
        'amount',
    ];

    public function match(): BelongsTo
    {
        return $this->belongsTo(MatchedTransaction::class);
    }
}
