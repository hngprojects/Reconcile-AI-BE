<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
// use Laravel\Socialite\Facades\Socialite;
// use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;
// use Illuminate\Support\Facades\Mail;
// use App\Mail\WelcomeEmail;
// use App\Models\Plan;
// use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
// use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Illuminate\Http\JsonResponse;
use App\Services\GoogleAuth\GoogleAuthService;

class GoogleAuthController extends Controller
{
    protected $googleAuthService;
    
    public function __construct(GoogleAuthService $googleAuthService)
    {
        $this->googleAuthService = $googleAuthService;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/google-login",
     *     summary="Login user using Google",
     *     description="Logs in a user using Google OAuth and returns an access token.",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"id_token"},
     *             @OA\Property(property="id_token", type="string", example="eyJhbGciOiJSUzI1NiIsImtpZ...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User successfully authenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Login Successful"),
     *             @OA\Property(property="access_token", type="string", example="eyJhbGciOiJIUzI1NiIsIn..."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="email", type="string", example="user@example.com"),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="avatar", type="string", example="https://example.com/avatar.jpg"),
     *                     @OA\Property(property="payment_plan", type="object")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid Token",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=401),
     *             @OA\Property(property="message", type="string", example="Invalid Token Payload")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=404),
     *             @OA\Property(property="message", type="string", example="Please register")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=422),
     *             @OA\Property(property="message", type="object")
     *         )
     *     )
     * )
     */
    public function loginGoogle(Request $request)
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'id_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status_code' => 422,
                'message' => $validator->errors()
            ], 422);
        }

        // Process login with Google
        $result = $this->googleAuthService->processLogin($request->id_token);
        
        return response()->json($result);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/google-register",
     *     summary="Register user using Google",
     *     description="Registers a new user using Google OAuth and returns an access token.",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"id_token"},
     *             @OA\Property(property="id_token", type="string", example="eyJhbGciOiJSUzI1NiIsImtpZ...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User successfully registered",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="User Created Successfully"),
     *             @OA\Property(property="access_token", type="string", example="eyJhbGciOiJIUzI1NiIsIn..."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="email", type="string", example="user@example.com"),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="avatar", type="string", example="https://example.com/avatar.jpg"),
     *                     @OA\Property(property="payment_plan", type="object")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid Token",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=401),
     *             @OA\Property(property="message", type="string", example="Invalid Token Payload")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="User already exists",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=403),
     *             @OA\Property(property="message", type="string", example="Please login into your account")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=422),
     *             @OA\Property(property="message", type="object")
     *         )
     *     )
     * )
     */
    public function registerWithGoogle(Request $request)
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'id_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status_code' => 422,
                'message' => $validator->errors()
            ], 422);
        }

        // Process registration with Google
        $result = $this->googleAuthService->processRegistration($request->id_token);
        
        return response()->json($result, $result['status_code']);
    }

    /**
     * Refresh the JWT token.
     */
    /**
     * @OA\Post(
     *     path="/api/v1/auth/refresh",
     *     summary="Refresh JWT Token",
     *     description="Refreshes the user's JWT token if the current token is valid.",
     *     tags={"Authentication"},
     *     @OA\Response(
     *         response=200,
     *         description="Token refreshed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="access_token", type="string", example="eyJhbGciOiJIUzI1NiIsIn...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Token is invalid or expired",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=401),
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Token has expired and cannot be refreshed")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Token is blacklisted",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=403),
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Token has been blacklisted")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=500),
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Could not refresh token")
     *         )
     *     )
     * )
     */
    public function refresh(): JsonResponse
    {
        try {
            // Attempt to refresh the token
            $newToken = JWTAuth::parseToken()->refresh();

            return response()->json([
                'status_code' => 200,
                'status' => 'success',
                'access_token' => $newToken,
            ], 200);
        } catch (TokenExpiredException $e) {
            return response()->json([
                'status_code' => 401,
                'status' => 'error',
                'message' => 'Token has expired and cannot be refreshed',
            ], 401);
        } catch (TokenInvalidException $e) {
            return response()->json([
                'status_code' => 401,
                'status' => 'error',
                'message' => 'Invalid token',
            ], 401);
        } catch (TokenBlacklistedException $e) {
            return response()->json([
                'status_code' => 403,
                'status' => 'error',
                'message' => 'Token has been blacklisted',
            ], 403);
        } catch (JWTException $e) {
            return response()->json([
                'status_code' => 500,
                'status' => 'error',
                'message' => 'Could not refresh token',
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/user",
     *     summary="Retrieve authenticated user details",
     *     description="Fetches the details of the authenticated user using a valid JWT token.",
     *     tags={"User"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User successfully fetched",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="User successfully fetched"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="john.doe@gmail.com"),
     *                     @OA\Property(property="avatar", type="string", example="https://example.com/avatar.jpg"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2024-03-10T12:00:00.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-03-10T12:30:00.000000Z"),
     *                     @OA\Property(property="email_verified", type="boolean", example=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="status_code", type="integer", example=401),
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     )
     * )
     */
    public function fetchUser(Request $request)
    {
        // $plan = $user->paymentPlan()->with('plan')->first();
        $user = $request->user()->load('paymentPlan.plan');
        $paymentPlan = $user->paymentPlan;

        return response()->json([
            'status_code' => 200,
            'status' => 'success',
            'message' => 'User successfully fetched',
            'data' => [
                'user' => $user,
                'plan' => $paymentPlan
            ]
        ]);
    }
}
