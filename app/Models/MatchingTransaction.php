<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Models\Ledger;
use App\Models\Statement;
use Illuminate\Support\Str;

class MatchingTransaction extends Pivot
{
    protected $table = "matched_statements";
    protected $fillable = [
        'statement_id',
        'ledger_id',
        'user_id',
        'status'
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
