<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BookkeepingLedger extends Model
{
    use HasFactory;
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

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function entries()
    {
        //return $this->hasMany(LedgerEntry::class, 'ledger_id');
    }

    public static function createDefaultForUser($user)
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
    }
}