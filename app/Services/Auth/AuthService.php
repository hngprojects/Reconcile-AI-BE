<?php

namespace App\Services\Auth;

use LaravelEasyRepository\BaseService;

interface AuthService extends BaseService{

    public function login($request);
    public function logout();
    public function register($request);
    public function forgotPassword($request);
    public function resetPassword($request);
    public function checkToken();
}
