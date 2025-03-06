<?php

namespace App\Services\Auth;

use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use LaravelEasyRepository\ServiceApi;
use App\Repositories\Auth\AuthRepository;

class AuthServiceImplement extends ServiceApi implements AuthService
{
    /**
     * don't change $this->mainRepository variable name
     * because used in extends service class
     */
    protected AuthRepository $mainRepository;

    public function __construct(AuthRepository $mainRepository)
    {
        $this->mainRepository = $mainRepository;
    }

    public function login($request)
    {
        try {
            $validated = $request->validated();

            // Attempt to authenticate using JWT
            if (!$token = JWTAuth::attempt($validated)) {
                return $this->setCode(401)->setMessage("Invalid credentials");
            }

            $user = Auth::user();

            return $this->setCode(200)
                ->setMessage("Login Success")
                ->setData([
                    'user' => new UserResource($user),
                    'token' => $token
                ]);
        } catch (\Exception $e) {
            return $this->setCode(400)
                ->setMessage("Login Failed")
                ->setError($e->getMessage());
        }
    }

    public function logout($request)
    {
        try {
            JWTAuth::parseToken()->invalidate();

            return $this->setCode(200)
                ->setMessage("Logout Success");
        } catch (\Exception $e) {
            return $this->setCode(400)
                ->setMessage("Logout Failed")
                ->setError($e->getMessage());
        }
    }
}
