<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManualMatch extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'reconciled_record_id',
        'file1_transaction',
        'file2_transaction',
        'is_matched',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'file1_transaction' => 'array',
        'file2_transaction' => 'array',
        'is_matched' => 'boolean',
    ];

    /**
     * Get the reconciled record that owns this manual match.
     */
    public function reconciledRecord()
    {
        return $this->belongsTo(ReconciledRecord::class, 'reconciled_record_id');
    }
}
