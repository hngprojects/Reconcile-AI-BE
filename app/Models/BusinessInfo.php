<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BusinessInfo extends Model
{
    use HasFactory, HasUuids;
    
    protected $guarded = ['id'];

    protected $keyType = 'string';
    // Disable auto-incrementing IDs
    public $incrementing = false;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bankAccounts()
    {
        return $this->hasMany(BankAccount::class, 'business_infos_id');
    }

    public function ledgers()
    {
        return $this->hasMany(CompanyLedger::class, 'business_infos_id');
    }
}
