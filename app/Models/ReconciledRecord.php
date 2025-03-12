<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Models\Reconciliation;

class ReconciledRecord extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['reconciliation_id', 'data'];

    protected $casts = [
        'data' => 'array',
    ];

    public function reconciliation()
    {
        return $this->belongsTo(Reconciliation::class);
    }
}
