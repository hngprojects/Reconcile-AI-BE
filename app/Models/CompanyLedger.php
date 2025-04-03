<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CompanyLedger extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];
    
    protected $keyType = 'string';
    // Disable auto-incrementing IDs
    public $incrementing = false;

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function business()
    {
        return $this->belongsTo(BusinessInfo::class, 'business_infos_id');
    }
}
