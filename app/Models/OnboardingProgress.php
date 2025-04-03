<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OnboardingProgress extends Model
{
    use HasFactory, HasUuids;
    protected $guarded = [];

    protected $keyType = 'string';
    // Disable auto-incrementing IDs
    public $incrementing = false;

    protected $casts = [
        'completed_basics' => 'boolean',
        'completed_bank' => 'boolean',
        'completed_ledger' => 'boolean',
        'completed_finish' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
