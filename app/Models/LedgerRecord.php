<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LedgerRecord extends Model
{
    protected $table = 'ledger_records';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'reconciliation_project_id', 'date', 'name_of_person', 'amount', 
        'other_information', 'vector_structured', 'vector_full'
    ];
    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'vector_structured' => 'array',
        'vector_full' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = Str::uuid()->toString();
            }
        });
    }

    public function reconciliationProject()
    {
        return $this->belongsTo(ReconciliationProject::class, 'reconciliation_project_id');
    }
}