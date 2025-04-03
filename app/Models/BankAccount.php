<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BankAccount extends Model
{
    use HasFactory, HasUuids;
    protected $guarded = ['id'];

    protected $keyType = 'string';
    // Disable auto-incrementing IDs
    public $incrementing = false;

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'is_primary' => 'boolean',
    ];

    public function business()
    {
        return $this->belongsTo(BusinessInfo::class, 'business_infos_id');
    }
    
}
