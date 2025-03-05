<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use App\Traits\HttpResponses;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordResetMail;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Hash;

class ForgotResetPasswordController extends Controller
{
    use HttpResponses;

    /**
     * @OA\Post(
     *     path="/api/v1/auth/forgot-password",
     *     summary="Request a password reset link",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password reset link sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Password reset link sent successfully"),
     *             @OA\Property(property="data", type="object", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="object", example={"email": {"The email field must be a valid email address."}}),
     *             @OA\Property(property="data", type="object", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Account with the specified email doesn't exist",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Account with the specified email doesn't exist"),
     *             @OA\Property(property="data", type="object", nullable=true)
     *         )
     *     )
     * )
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email:rfc'
        ]);

        if ($validator->fails()) {
            return $this->apiResponse(message: $validator->errors(), status_code: 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->apiResponse(message: 'Account with the specified email doesn\'t exist', status_code: 400);
        }

        // Create a reset token
        $token = Str::random(60);

        // Store the token in the password_reset_tokens table
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => $token,
                'created_at' => Carbon::now(),
            ]
        );

        // Generate the reset link
        $resetUrl = url("/reset-password?token={$token}&email={$request->email}");

        // Send the reset link via email
        Mail::to($user->email)->send(new PasswordResetMail($resetUrl));

        return $this->apiResponse(message: 'Password reset link sent successfully');
    }
}
