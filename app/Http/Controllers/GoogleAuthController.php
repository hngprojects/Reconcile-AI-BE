<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeEmail;
use App\Models\Plan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Illuminate\Http\JsonResponse;

class GoogleAuthController extends Controller
{
    /**
     * Authenticate using Google.
     */
    /**
     * @OA\Post(
     *     path="/api/v1/auth/google-login",
     *     summary="Authenticate using Google",
     *     description="Logs in or registers a user using Google OAuth and returns an access token.",
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
     *             @OA\Property(property="message", type="string", example="User Created Successfully"),
     *             @OA\Property(property="access_token", type="string", example="eyJhbGciOiJIUzI1NiIsIn..."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="email", type="string", example="user@example.com"),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="avatar", type="string", example="https://example.com/avatar.jpg")
     *                 ),
     *                 @OA\Property(property="plan", type="object",
     *                     @OA\Property(property="user_id", type="integer", example=1),
     *                     @OA\Property(property="price", type="integer", example=0),
     *                     @OA\Property(property="plan", type="string", example="Basic")
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

        $isNewUser = false;

        // Extract Google user data from the request
        $idToken = $request->id_token;

        $response = Http::get("https://www.googleapis.com/oauth2/v3/tokeninfo?id_token={$idToken}");
        Log::info('Response', ['data' => $response]);
        if($response->successful()) {
            $payload = $response->json();
            if (isset($payload['sub']) && isset($payload['email'])) {
                $email = $payload['email'];
                $firstName = $payload['given_name'] ?? null;
                $lastName = $payload['family_name'] ?? null;
                $avatarUrl = $payload['picture'] ?? null;

                // Create or update user
                $user = User::where('email', $email)->first();
                if (!$user) {

                    // Hash::make(Str::random(12))

                    $user = User::create([
                        'name' => $firstName . ' ' . $lastName,
                        'email' => $email,
                        'avatar' => $avatarUrl,
                        'password' => "", // Random password
                    ]);

                    // Get the Basic plan
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
                        'price'         => 0,
                        'plan'          => 'Basic',
                        'start_date'    => now(),
                        'expire_date'   => now()->addDays($basicPlan->plan_length),
                    ]);

                    $isNewUser = true;
                }

                $token = JWTAuth::fromUser($user);

                $getStartedUrl = env('FRONTEND_URL', 'https://reconxi.com') . '/file-upload?token=' . $token;

                if ($isNewUser) {
                    Mail::to($user->email)->queue(new WelcomeEmail($user, $getStartedUrl));
                }

                $plan = $user->paymentPlan;

                return response()->json([
                    'status_code' => 200,
                    'message' => 'User Created Successfully',
                    'access_token' => $token,
                    'data' => [
                        'user' => [
                            'id' => $user->id,
                            'email' => $user->email,
                            'name' => $user->name,
                            'avatar' => $avatarUrl,
                        ],
                        'plan' => $plan
                    ]
                ]);
            } else {
                return response()->json([
                    'status_code' => 401,
                    'message' => 'Invalid Token Payload'
                ], 401);
            }
        } else {
            return response()->json([
                'status_code' => 401,
                'message' => 'Invalid Token: ' . $response->body()
            ], 401);
        }
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
     *     path="/api/v1/auth/google",
     *     summary="Redirect to Google for authentication",
     *     description="Redirects the user to Google's OAuth login page to authenticate.",
     *     tags={"Authentication"},
     *     @OA\Response(
     *         response=302,
     *         description="Redirect to Google's OAuth login page"
     *     )
     * )
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * @OA\Get(
     *     path="/api/v1/auth/google/callback",
     *     summary="Handle Google OAuth callback",
     *     description="Handles the callback from Google OAuth after the user logs in. Creates or retrieves the user and returns a JWT token.",
     *     tags={"Authentication"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful authentication",
     *         @OA\JsonContent(
     *             @OA\Property(property="user", type="object", description="Authenticated user details",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john.doe@gmail.com"),
     *                 @OA\Property(property="avatar", type="string", example="https://example.com/avatar.jpg")
     *              ),
     *             @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...", description="JWT token for authenticated requests")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Authentication failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            // Try to retrieve the user from Google
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Exception $e) {
            // Redirect back to the frontend with an error message
            return redirect()->away(env('FRONTEND_URL', 'https://reconxi.com') . '/login?error=google_auth_failed');
        }

        $ip = $request->ip(); // Get user's IP address

        $isNewUser = false; // Flag to track if user is new

        // Find an existing registered user or create a new one
        $user = User::where('email', $googleUser->email)->first();

        if (!$user) {
            // Create new user
            $user = User::create([
                'name' => $googleUser->name,
                'email' => $googleUser->email,
                'avatar' => $googleUser->avatar,
                'password' => "", // Random password
            ]);

            // Create a new payment plan
            $user->paymentPlan()->create([
                'user_id' => $user->id,
                'price' => 0,
                'plan' => 'Basic',
            ]);

            $isNewUser = true; // User is newly created
        }

        // Generate JWT token
        $token = JWTAuth::fromUser($user);

        $getStartedUrl = env('FRONTEND_URL', 'https://reconxi.com') . '/file-upload?token=' . $token;

        // Send welcome email asynchronously only for new users
        if ($isNewUser) {
            Mail::to($user->email)->queue(new WelcomeEmail($user, $getStartedUrl));
        }

        return redirect()->away($getStartedUrl);
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
    /* public function fetchUser(Request $request)
    {
        // $user = $request->user();
        $user = $request->user()->load('paymentPlan.plan');
        // dd($user);
        $plan = $user->paymentPlan;
        // $userPlan = $plan->plan();

        return response()->json([
            'status_code' => 200,
            'status' => 'success',
            'message' => 'User successfully fetched',
            'data' => [
                'user' => $request->user(),
                'plan' => $plan ?? null,
                'userPlan' => $plan ? $plan->plan : null
            ]
        ]);
    } */

    public function fetchUser(Request $request)
    {
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
