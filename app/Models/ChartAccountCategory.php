<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartAccountCategory extends Model
{
    use HasUuids;
    protected $table = 'account_chart_categories';
    protected $fillable = [
        'title',
        'description',
        'is_active',
        'is_required',
    ];


    public function user(): HasMany
    {
        return $this->hasMany(User::class, 'user_chart_categories', 'account_chart_category_id', 'user_id');
    }
}
