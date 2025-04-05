<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookkeepingLedger extends Model
{
    use HasFactory, HasUuids;
    protected $table = 'bookkeeping_ledgers';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['id', 'user_id', 'name', 'description', 'is_default', 'is_active', 'categories'];
    protected $casts = [
        'id' => 'string',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'categories' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function entries()
    {
        return $this->hasMany(LedgerEntry::class, 'ledger_id');
    }

    /* public static function createDefaultForUser($user)
    {
        return self::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'name' => 'General Ledger',
            'description' => 'Default ledger for all transactions',
            'is_default' => true,
            'is_active' => true,
            'categories' => ['Assets', 'Revenue', 'Liabilities', 'Expenses', 'Equity'],
        ]);
    } */

    public static function getDefaultConfig(): array
    {
        return [
            'general' => [
                'name' => 'General Ledger',
                'description' => 'Main accounting ledger for all transactions',
                'categories' => ['income', 'expense', 'asset'],
                'is_default' => true
            ],
            'vendor' => [
                'name' => 'Vendor Ledger',
                'description' => 'Track account payable and vendor transactions',
                'categories' => ['payable', 'purchase'],
                'is_default' => false
            ],
            'customer' => [
                'name' => 'Customer Ledger',
                'description' => 'Track account receivable and customer transactions',
                'categories' => ['receivable', 'sale'],
                'is_default' => false
            ]
        ];
    }

    public function businessInfo(): BelongsTo
    {
        return $this->belongsTo(BusinessInfo::class);
    }
}