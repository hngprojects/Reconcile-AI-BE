<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\MatchedTransaction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Reconciliation;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Statement extends Model
{
    /** @use HasFactory<\Database\Factories\StatementFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'date',
        'person',
        'amount',
        'other_information',
        'reconciliation_id'
    ];
    protected $keyType = 'string';
    public $incrementing = false;

    public function matched(): BelongsTo
    {
        return $this->belongsTo(MatchedTransaction::class);
    }

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(Reconciliation::class);
    }
}
