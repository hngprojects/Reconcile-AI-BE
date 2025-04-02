<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;
use App\Models\Reconciliation;
use Illuminate\Support\Str;

class UserFile extends Model
{
    protected $fillable = [
        'file_name',
        'type',
        'user_id',
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

    public function reconciliations(): BelongsToMany
    {
        return $this->belongsToMany(Reconciliation::class, 'reconciliation_files', 'file_id', 'reconciliation_id');
    }
}
