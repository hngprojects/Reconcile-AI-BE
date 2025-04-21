<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,              // Related model
            'user_chart_categories',  // Pivot table name
            'account_chart_category_id', // Foreign key on pivot table
            'user_id'                 // Related key on pivot table
        );
    }
}
