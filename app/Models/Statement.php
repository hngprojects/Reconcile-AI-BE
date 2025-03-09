<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relation\BelongsTo;
use App\Models\MatchedTransaction;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Statement extends Model
{
    /** @use HasFactory<\Database\Factories\StatementFactory> */
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
