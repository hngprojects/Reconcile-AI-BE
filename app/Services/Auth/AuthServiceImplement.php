<?php

namespace App\Services\Auth;

use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use LaravelEasyRepository\ServiceApi;
use App\Repositories\Auth\AuthRepository;
use App\Mail\WelcomeEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Events\Registered;
use App\Models\User;

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

    public function signUp($request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email:rfc|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status_code' => 422,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }


        try {
            DB::beginTransaction();

            $user = User::create([
                'id' => Str::uuid(),
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'name' => $request->name,
            ]);

            $token = JWTAuth::fromUser($user);

            DB::commit();

            // registeration email should be sent here..
            event(new Registered($user));

            Mail::to($user->email)->send(new WelcomeEmail($user));
            return response()->json([
                'status_code' => 201,
                'message' => 'User Created Successfully',
                'access_token' => $token,
            ], 201);
            
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            return response()->json([
                'status_code' => 500,
                'message' => 'Registration unsuccessful: ' . $th->getMessage(),
            ], 500);
        }
    }
}
