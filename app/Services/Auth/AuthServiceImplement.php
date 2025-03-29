<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Exceptions\JWTException;
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
            $plan = $user->paymentPlan;

            return $this->setCode(200)
                ->setMessage("Login Success")
                ->setData([
                    'user' => new UserResource($user),
                    'plan' => $plan,
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
    /* public function logout()
    {
        try {
            JWTAuth::parseToken()->invalidate();

            return $this->setCode(200)
                ->setMessage("Logout Success");
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return $this->setCode(400)
                ->setMessage("Logout Failed")
                ->setError($this->getErrorCode($e));
        }
    }

    private function getErrorCode($exception)
    {
        if ($exception instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {
            return 'token expired';
        } elseif ($exception instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) {
            return 'token invalid';
        } else {
            return 'token absent';
        }
    } */

    public function logout()
    {
        try {
            if (!JWTAuth::getToken()) {
                return $this->setCode(400)
                    ->setMessage("Token not provided")
                    ->setError('token_absent');
            }

            JWTAuth::invalidate(JWTAuth::getToken());

            return $this->setCode(200)
                ->setMessage("Logout Success");
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return $this->setCode(400)
                ->setMessage("Logout Failed")
                ->setError('token_expired');
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return $this->setCode(400)
                ->setMessage("Logout Failed")
                ->setError('token_invalid');
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return $this->setCode(400)
                ->setMessage("Logout Failed")
                ->setError('token_error');
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

            $basicPlan = Plan::where('plan', 'Basic')->first();

            if (!$basicPlan) {
                // Create Basic plan if it doesn't exist
                $basicPlan = Plan::create([
                    'name' => 'Basic Plan',
                    'plan' => 'Basic',
                    'description' => 'Free trial for 7 days with 5 reconciliations.',
                    'plan_length' => 30,
                    'reconciliations_per_month' => 5,
                    'amount' => 0.00,
                ]);
            }

            // Create a new payment plan
            $user->paymentPlan()->create([
                'user_id'       => $user->id,
                'plan_id'       => $basicPlan->id,
                'start_date'    => now(),
                'expire_date'   => now()->addDays($basicPlan->plan_length),
            ]);

            $token = JWTAuth::fromUser($user);

            return $this->setCode(200)
                ->setMessage("User account registration successful")
                ->setData([
                    'user' => new UserResource($user),
                    'plan' => $basicPlan,
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

    /**
     * Handles the token validation process for the application.
     */

    public function checkToken()
    {
        try {
            $token = JWTAuth::parseToken();
            $user = $token->authenticate();

            if ($user) {
                return response()->json(['message' => 'Token is valid', 'user' => $user]);
            }
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['error' => 'Token expired'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['error' => 'Token invalid'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['error' => 'Token absent'], 401);
        }
    }
}
