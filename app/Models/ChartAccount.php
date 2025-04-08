<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChartAccount extends Model
{
    use HasUuids;
    protected $table = 'chart_accounts';
    protected $fillable = [
        'user_id',
        'account_chart_category_id',
        'description',
        'account_number',
        'account_name',
        'balance'
    ];

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo {
        return $this->belongsTo(ChartAccountCategory);
    }
}
