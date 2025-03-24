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
        'city'

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

    public function paymentPlan()
    {
        return $this->hasOne(PaymentPlan::class);
    }
}
