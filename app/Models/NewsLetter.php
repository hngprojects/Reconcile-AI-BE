<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NewsLetter extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'subscribed',
        'full_name',
        'business_name',
        'phone_number',
    ];
}
