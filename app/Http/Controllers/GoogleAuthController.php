<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

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
     *                 @OA\Property(property="email", type="string", example="john.doe@gmail.com")
     *             ),
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
    public function handleGoogleCallback()
    {
        $googleUser = Socialite::driver('google')->stateless()->user();

        // Check if user exists
        $user = User::where('email', $googleUser->email)->first();

        if (!$user) {
            // Create new user
            $user = User::create([
                'name' => $googleUser->name,
                'email' => $googleUser->email,
                'password' => bcrypt(Str::random(16)), // Random password
            ]);
        }

        // Generate JWT token
        $token = JWTAuth::fromUser($user);

        return response()->json([
            "status" => "success",
            'message' => 'Successfully logged in',
            'data' => $user,
            'token' => $token,
        ], 200);
    }
}
