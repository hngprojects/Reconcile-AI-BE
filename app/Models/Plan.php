<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Plan extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = []; // Protects all fields from mass assignment

    public $incrementing = false; // Disables auto-incrementing IDs
    protected $keyType = 'string'; // UUIDs are strings

    protected $casts = [
        'plan_length' => 'integer', // Ensures plan_length is stored as an integer
        'reconciliations_per_month' => 'integer', // Ensures reconciliations_per_month is stored as an integer
        'amount' => 'decimal:2', // Ensures amount is returned as a decimal with 2 decimal places
    ];
}
