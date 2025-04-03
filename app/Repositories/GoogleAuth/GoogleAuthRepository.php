<?php

namespace App\Repositories\GoogleAuth;

use App\Models\User;
use LaravelEasyRepository\Repository;

interface GoogleAuthRepository extends Repository{

    /**
     * Validate Google ID token
     * 
     * @param string $idToken
     * @return array|null
     */
    public function validateGoogleToken(string $idToken);
    
    /**
     * Find or create user from Google payload
     * 
     * @param array $payload
     * @return array
     */
    public function findOrCreateUser(array $payload);
    
    /**
     * Generate JWT token for user
     * 
     * @param User $user
     * @return string
     */
    public function generateToken(User $user);
    
    /**
     * Format response data
     * 
     * @param User $user
     * @param string $token
     * @param string $message
     * @param int $statusCode
     * @return array
     */
    public function formatResponse(User $user, string $token, string $message, int $statusCode);
}
