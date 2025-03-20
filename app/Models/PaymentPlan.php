<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentPlan extends Model
{
    //

    protected $fillable = [
        'user_id',
        'price',
        'plan',

    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
