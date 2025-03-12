<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relation\BelongsTo;
use App\Models\MatchedTransaction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Reconciliation;
use Illuminate\Support\Str;

class Ledger extends Model
{
    /** @use HasFactory<\Database\Factories\LedgerFactory> */
    use HasFactory;

    protected $fillable = [
        'date',
        'description',
        'amount',
        'reconciliation_id'
    ];
    protected $keyType = 'string';


    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(MatchedTransaction::class);
    }

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(Reconciliation::class);
    }
}
