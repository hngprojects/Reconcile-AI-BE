<?php

namespace App\Services\GoogleAuth;

use LaravelEasyRepository\BaseService;

interface GoogleAuthService extends BaseService
{
    /**
     * Process Google login
     * 
     * @param string $idToken
     * @return array
     */
    public function processLogin(string $idToken);
    
    /**
     * Process Google registration
     * 
     * @param string $idToken
     * @return array
     */
    public function processRegistration(string $idToken);
}