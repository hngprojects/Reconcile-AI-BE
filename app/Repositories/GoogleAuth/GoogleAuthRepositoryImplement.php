<?php

namespace App\Repositories\GoogleAuth;

use LaravelEasyRepository\Implementations\Eloquent;
use App\Models\User;
use App\Models\Plan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

class GoogleAuthRepositoryImplement extends Eloquent implements GoogleAuthRepository{

    /**
    * Model class to be used in this repository for the common methods inside Eloquent
    * Don't remove or change $this->model variable name
    * @property Model|mixed $model;
    */
    protected User $model;

    public function __construct(User $model)
    {
        $this->model = $model;
    }

    public function validateGoogleToken(string $idToken): ?array
    {
        $response = Http::get("https://www.googleapis.com/oauth2/v3/tokeninfo?id_token={$idToken}");
        Log::info('Google token validation response', ['data' => $response]);
        
        if (!$response->successful()) {
            return null;
        }
        
        $payload = $response->json();
        
        if (!isset($payload['sub']) || !isset($payload['email'])) {
            return null;
        }
        
        return $payload;
    }

    public function findOrCreateUser(array $payload): array
    {
        $email = $payload['email'];
        $user = User::where('email', $email)->first();
        
        if ($user) {
            return ['user' => $user, 'is_new_user' => false];
        }

        $user = User::create([
            'name' => ($payload['given_name'] ?? '') . ' ' . ($payload['family_name'] ?? ''),
            'email' => $email,
            'avatar' => $payload['picture'] ?? null,
            'password' => '',
        ]);

        $this->assignBasicPlan($user);

        return ['user' => $user, 'is_new_user' => true];
    }

    public function generateToken($user): string
    {
        $customTTL = config('jwt.ttl');
        JWTAuth::factory()->setTTL($customTTL);
        return JWTAuth::fromUser($user);
    }

    public function assignBasicPlan($user): void
    {
        $basicPlan = Plan::firstOrCreate(
            ['plan' => 'Basic'],
            [
                'name' => 'Basic Plan',
                'description' => 'Free trial for 7 days with 5 reconciliations.',
                'plan_length' => 30,
                'reconciliations_per_month' => 5,
                'amount' => 0.00,
            ]
        );

        $user->paymentPlan()->create([
            'user_id' => $user->id,
            'plan_id' => $basicPlan->id,
            'start_date' => now(),
            'expire_date' => now()->addDays($basicPlan->plan_length),
        ]);
    }
}
