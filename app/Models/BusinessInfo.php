<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\{
    HasMany,
    BelongsTo
};

class BusinessInfo extends Model
{
    use HasFactory, HasUuids;
    protected $guarded = [];
    protected $keyType = 'string';
    public $incrementing = false;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class);
    }

    public function ledgers(): HasMany
    {
        return $this->hasMany(BookkeepingLedger::class);
    }
}
