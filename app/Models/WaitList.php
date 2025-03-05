<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaitList extends Model
{
    use HasFactory;

    protected $table = 'waitlists'; // Specify the actual table name

    protected $fillable = [
        'email',
    ];

    protected $casts = [
        'email' => 'string',
    ];
}
