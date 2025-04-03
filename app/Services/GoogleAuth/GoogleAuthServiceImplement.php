<?php

namespace App\Services\GoogleAuth;

use LaravelEasyRepository\ServiceApi;
use App\Repositories\GoogleAuth\GoogleAuthRepository;
use Illuminate\Http\Exceptions\HttpResponseException;

class GoogleAuthServiceImplement extends ServiceApi implements GoogleAuthService
{
    /**
     * don't change $this->mainRepository variable name
     * because used in extends service class
     */
    protected GoogleAuthRepository $mainRepository;

    public function __construct(GoogleAuthRepository $mainRepository)
    {
        $this->mainRepository = $mainRepository;
    }

    /**
     * Process Google login
     * 
     * @param string $idToken
     * @return array
     */
    public function processLogin(string $idToken)
    {
        // Validate Google token
        $payload = $this->mainRepository->validateGoogleToken($idToken);
        if (!$payload) {
            throw new HttpResponseException(response()->json([
                'status_code' => 401,
                'message' => 'Invalid Token'
            ], 401));
        }

        // Find existing user
        $result = $this->mainRepository->findOrCreateUser($payload);
        $user = $result['user'];
        
        // For login, we expect the user to exist already
        if ($result['isNewUser']) {
            throw new HttpResponseException(response()->json([
                'status_code' => 404,
                'message' => 'Please register'
            ], 404));
        }

        // Generate token and format response
        $token = $this->mainRepository->generateToken($user);
        return $this->mainRepository->formatResponse($user, $token, 'Login Successful', 200);
    }
    
    /**
     * Process Google registration
     * 
     * @param string $idToken
     * @return array
     */
    public function processRegistration(string $idToken)
    {
        // Validate Google token
        $payload = $this->mainRepository->validateGoogleToken($idToken);
        if (!$payload) {
            throw new HttpResponseException(response()->json([
                'status_code' => 401,
                'message' => 'Invalid Token'
            ], 401));
        }

        // Find or create user
        $result = $this->mainRepository->findOrCreateUser($payload);
        $user = $result['user'];
        
        // For registration, we expect to create a new user
        if (!$result['isNewUser']) {
            throw new HttpResponseException(response()->json([
                'status_code' => 403,
                'message' => 'Please login into your account'
            ], 403));
        }

        // Generate token and format response
        $token = $this->mainRepository->generateToken($user);
        return $this->mainRepository->formatResponse($user, $token, 'User Created Successfully', 201);
    }
}