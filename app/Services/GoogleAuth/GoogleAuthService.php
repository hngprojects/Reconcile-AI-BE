<?php

namespace App\Services\GoogleAuth;

use LaravelEasyRepository\BaseService;
use Illuminate\Http\Request;

interface GoogleAuthService extends BaseService{

    public function loginWithGoogle(Request $request): array;
}
