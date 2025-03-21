<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeEmail;

class GoogleAuthController extends Controller
{
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
        $googleUser = Socialite::driver('google')->stateless()->user();
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

        return redirect()->to($getStartedUrl);
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
        $user = $request->user();
        // dd($user);
        $plan = $user->paymentPlan;

        return response()->json([
            'status_code' => 200,
            'status' => 'success',
            'message' => 'User successfully fetched',
            'data' => [
                'user' => $request->user(),
                'plan' => $plan ?? null,
            ]
        ]);
    }
}
