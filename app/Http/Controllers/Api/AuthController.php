<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AuthLogin;
use App\Http\Requests\Auth\AuthRegister;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/login",
     *     summary="User login",
     *     description="Authenticates a user and returns a JWT token",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="test@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Login Success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="test@example.com"),
     *                 ),
     *                 @OA\Property(property="token", type="string", example="eyJhbGciOiJIUzI1NiIsInR...")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="integer", example=401),
     *             @OA\Property(property="message", type="string", example="Invalid credentials")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Login failed due to validation error or server issue",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="Login Failed"),
     *             @OA\Property(property="error", type="string", example="Invalid email format")
     *         )
     *     )
     * )
     */
    public function login(AuthLogin $request): JsonResponse
    {
        return $this->authService->login($request)->toJson();
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/logout",
     *     summary="User logout",
     *     description="Logs out the authenticated user and invalidates the token",
     *     tags={"Authentication"},
     *     security={{ "bearerAuth":{} }},
     *     @OA\Response(
     *         response=200,
     *         description="Logout successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Logout successful")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized, token missing or invalid",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="integer", example=401),
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */
    public function logout(): JsonResponse
    {
        return $this->authService->logout()->toJson();
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/register",
     *     summary="User registration",
     *     description="Registers a new user and returns a JWT token",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "password_confirmation"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="test@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Registration successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="integer", example=201),
     *             @OA\Property(property="message", type="string", example="Registration Successful"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="test@example.com"),
     *                 ),
     *                 @OA\Property(property="token", type="string", example="eyJhbGciOiJIUzI1NiIsInR...")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Registration failed due to validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="Registration Failed"),
     *             @OA\Property(property="error", type="string", example="Email already taken")
     *         )
     *     )
     * )
     */
    public function register(AuthRegister $request): JsonResponse
    {
        return $this->authService->register($request)->toJson();
    }
}
