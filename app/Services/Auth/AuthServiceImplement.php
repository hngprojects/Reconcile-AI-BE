<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use LaravelEasyRepository\ServiceApi;
use Illuminate\Support\Facades\Password;
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

    /**
     * Handles the login process for the application.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse | \App\Services\Auth\AuthServiceImplement
     */
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

    /**
     * Handles the logout process for the application.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse | \App\Services\Auth\AuthServiceImplement
     */
    public function logout()
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

    /**
     * Handles the registration process for the application.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse | \App\Services\Auth\AuthServiceImplement
     */
    public function register($request)
    {
        try {
            $validated = $request->validated();
            $user = $this->mainRepository->register($validated);

            $token = JWTAuth::fromUser($user);

            return $this->setCode(200)
                ->setMessage("User account registration successful")
                ->setData([
                    'user' => new UserResource($user),
                    'token' => $token
                ]);
        } catch (\Exception $e) {
            return $this->setCode(400)
                ->setMessage("User account registration failed")
                ->setError($e->getMessage());
        }
    }

    /**
     * Handles the forgot password process for the application.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse | \App\Services\Auth\AuthServiceImplement
     */
    public function forgotPassword($request)
    {
        try {
            $validated = $request->validated();
            $status = Password::sendResetLink($validated);
            if ($status === Password::RESET_LINK_SENT) {
                return $this->setCode(200)->setMessage("Password reset link sent.");
            }

            return $this->setCode(400)->setMessage("Unable to send reset link.");
        } catch (\Exception $e) {
            return $this->setCode(400)
                ->setMessage("Forgot Password Failed")
                ->setError($e->getMessage());
        }
    }

    /**
     * Handles the password reset process for the application.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse | \App\Services\Auth\AuthServiceImplement
     */
    public function resetPassword($request)
    {
        try {
            $validated = $request->validated();
            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function (User $user, string $password) {
                    $user->forceFill(['password' => Hash::make($password)])->save();
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return $this->setCode(200)->setMessage("Password has been reset. Return to Login page to continue.");
            }

            return $this->setCode(400)->setMessage("Invalid token or email.");

        } catch (\Exception $e) {
            return $this->setCode(400)
                ->setMessage("Password reset Failed")
                ->setError($e->getMessage());
        }
    }
}
