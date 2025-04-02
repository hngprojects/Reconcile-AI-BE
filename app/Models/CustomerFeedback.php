<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class CustomerFeedback extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected $keyType = 'string';
    // Disable auto-incrementing IDs
    public $incrementing = false;
}
