<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class BillingTransaction extends Model
{
    use HasUuids;
    protected $fillable = [
        'user_id',
        'description',
        'status',
        'plan',
        'amount'
    ];
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
