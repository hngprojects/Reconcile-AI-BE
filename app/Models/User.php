<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Passwords\CanResetPassword;
use App\Notifications\Auth\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable implements JWTSubject, CanResetPasswordContract
{
    use HasFactory, Notifiable, HasApiTokens, CanResetPassword;

    /**
     * Get the identifier that will be stored in the JWT subject claim.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey(); // Usually, this is the `id` field.
    }

    /**
     * Return a key-value array containing any custom claims to be added to the JWT.
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'country',
        'city',
        'phone_number',

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Override password reset notification to send a custom API link.
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /* public function paymentPlan()
    {
        return $this->hasOne(PaymentPlan::class);
    } */

    /* public function paymentPlans()
    {
        return $this->hasMany(PaymentPlan::class);
    } */

    // In User.php
    public function paymentPlan()
    {
        return $this->hasOne(PaymentPlan::class, 'user_id')->where('is_active', true);
    }

    // Remove all other payment plan relationships

    public function currentPaymentPlan()
    {
        return $this->hasOne(PaymentPlan::class)
            ->where('is_active', true)
            ->with('plan');
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class);
    }

    public function businessInfos(): HasMany
    {
        return $this->hasMany(BusinessInfo::class);
    }

    public function ledgers(): HasMany
    {
        return $this->hasMany(BookkeepingLedger::class);
    }

    public function user(): HasMany
    {
        return $this->hasMany(ChartAccountCategory::class, 'user_chart_categories', 'user_id', 'account_chart_category_id');
    }
}
