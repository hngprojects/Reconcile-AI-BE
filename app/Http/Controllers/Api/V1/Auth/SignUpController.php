<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeEmail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Auth\Events\Registered;

class SignUpController extends Controller
{
    /**
 * @OA\Post(
 *     path="/api/v1/auth/sign-up",
 *     summary="User Registration",
 *     description="Registers a new user and returns a success message with access token or validation errors.",
 *     tags={"Authentication"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"name", "email", "password"},
 *             @OA\Property(property="name", type="string", example="John Doe"),
 *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
 *             @OA\Property(property="password", type="string", format="password", example="strongPassword123")
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="User registered successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="User registered successfully"),
 *             @OA\Property(property="user", type="object",
 *                 @OA\Property(property="status_code", type="integer", example=201),
 *                 @OA\Property(property="message", type="string", example="John Doe"),
 *                 @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOidjdjdIUzIjfjfjJ9.)
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation Error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Validation error"),
 *             @OA\Property(property="errors", type="object",
 *                 @OA\Property(property="email", type="array",
 *                     @OA\Items(type="string", example="The email field is required.")
 *                 ),
 *                 @OA\Property(property="password", type="array",
 *                     @OA\Items(type="string", example="The password must be at least 8 characters.")
 *                 )
 *             )
 *         )
 *     )
 * )
 */
    public function store(Request $request)
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
            print($th);
            DB::rollBack();
            return response()->json([
                'status_code' => 500,
                'message' => 'Registration unsuccessful: ' . $th->getMessage(),
            ], 500);
        }
    }
}
