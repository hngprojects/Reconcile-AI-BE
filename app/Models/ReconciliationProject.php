<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ReconciliationProject extends Model
{
    protected $table = 'reconciliation_projects';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'id', 'user_id', 'status', 'progress', 'statement_file', 'ledger_file', 'ai_option', 'result'
    ];
    protected $casts = [
        'result' => 'array',
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function statementRecords()
    {
        return $this->hasMany(StatementRecord::class, 'reconciliation_project_id');
    }

    public function ledgerRecords()
    {
        return $this->hasMany(LedgerRecord::class, 'reconciliation_project_id');
    }
}