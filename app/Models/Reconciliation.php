<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\User;
use App\Models\UserFile;
use App\Models\ReconciledRecord;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Reconciliation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
    ];
    protected $keyType = 'string';


    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function files(): BelongsToMany
    {
        return $this->belongsToMany(UserFile::class, 'reconciliation_files', 'reconciliation_id', 'file_id');
    }

    public function record()
    {
        return $this->hasOne(ReconciledRecord::class);
    }

    public function statementFiles()
    {
        return $this->belongsToMany(StatementFile::class, 'reconciliation_statement_files');
    }

    public function ledgers()
    {
        return $this->belongsToMany(Ledger::class, 'reconciliation_ledgers');
    }


}
