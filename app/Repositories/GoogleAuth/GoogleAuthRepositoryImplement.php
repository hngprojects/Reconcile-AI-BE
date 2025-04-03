<?php

namespace App\Repositories\GoogleAuth;

use LaravelEasyRepository\Implementations\Eloquent;

use App\Models\User;
use App\Models\Plan;
use App\Mail\WelcomeEmail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Facades\JWTAuth;

class GoogleAuthRepositoryImplement extends Eloquent implements GoogleAuthRepository{

    /**
     * Model class to be used in this repository for the common methods inside Eloquent
     * Don't remove or change $this->model variable name
     * @property Model|mixed $model;
     */
    protected $model;

    public function __construct(User $model)
    {
        $this->model = $model;
    }

    /**
     * Validate and decode Google ID token
     * 
     * @param string $idToken
     * @return array|null
     */
    public function validateGoogleToken(string $idToken)
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
    
    /**
     * Find or create user from Google data
     * 
     * @param array $payload
     * @return array
     */
    public function findOrCreateUser(array $payload)
    {
        $email = $payload['email'];
        $user = $this->model->where('email', $email)->first();
        
        if ($user) {
            return [
                'user' => $user,
                'isNewUser' => false
            ];
        }
        
        $firstName = $payload['given_name'] ?? '';
        $lastName = $payload['family_name'] ?? '';
        $avatarUrl = $payload['picture'] ?? null;
        
        $user = $this->model->create([
            'name' => trim($firstName . ' ' . $lastName),
            'email' => $email,
            'avatar' => $avatarUrl,
            'password' => "", // Empty password for OAuth users
        ]);
        
        // Assign basic plan to new user
        $this->assignBasicPlan($user);
        
        // Send welcome email with token
        $this->sendWelcomeEmail($user);
        
        return [
            'user' => $user,
            'isNewUser' => true
        ];
    }
    
    /**
     * Assign basic plan to user
     * 
     * @param User $user
     * @return void
     */
    private function assignBasicPlan(User $user)
    {
        $basicPlan = Plan::where('plan', 'Basic')->first();
        
        if (!$basicPlan) {
            $basicPlan = Plan::create([
                'name' => 'Basic Plan',
                'plan' => 'Basic',
                'description' => 'Free trial for 7 days with 5 reconciliations.',
                'plan_length' => 30,
                'reconciliations_per_month' => 5,
                'amount' => 0.00,
            ]);
        }
        
        $user->paymentPlan()->create([
            'user_id' => $user->id,
            'plan_id' => $basicPlan->id,
            'start_date' => now(),
            'expire_date' => now()->addDays($basicPlan->plan_length),
        ]);
    }
    
    /**
     * Generate JWT token for user
     * 
     * @param User $user
     * @return string
     */
    public function generateToken(User $user)
    {
        $customTTL = config('jwt.ttl');
        JWTAuth::factory()->setTTL($customTTL);
        return JWTAuth::fromUser($user);
    }
    
    /**
     * Send welcome email to new user
     * 
     * @param User $user
     * @return void
     */
    private function sendWelcomeEmail(User $user)
    {
        $token = $this->generateToken($user);
        $getStartedUrl = env('FRONTEND_URL', 'https://reconxi.com') . '/file-upload?token=' . $token;
        Mail::to($user->email)->queue(new WelcomeEmail($user, $getStartedUrl));
    }
    
    /**
     * Format response data
     * 
     * @param User $user
     * @param string $token
     * @param string $message
     * @param int $statusCode
     * @return array
     */
    public function formatResponse(User $user, string $token, string $message, int $statusCode = 200)
    {
        return [
            'status_code' => $statusCode,
            'message' => $message,
            'access_token' => $token,
            'data' => [
                'user' => [...$user->toArray(), 'payment_plan' => $user->paymentPlan],
            ]
        ];
    }
}