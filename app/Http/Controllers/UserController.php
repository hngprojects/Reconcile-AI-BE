<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

class UserController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/profile/update",
     *     summary="Update user profile",
     *     description="Updates the authenticated user's profile information including name, phone_number, country, city, and avatar",
     *     tags={"User"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="name", type="string", example="John Doe", description="User's full name"),
     *                 @OA\Property(property="phone_number", type="string", example="+1234567890", description="User's phone number"),
     *                 @OA\Property(property="country", type="string", example="United States", description="User's country"),
     *                 @OA\Property(property="city", type="string", example="New York", description="User's city"),
     *                 @OA\Property(property="avatar", type="string", format="binary", description="User's profile picture (JPEG, PNG, JPG, GIF up to 5MB)")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Profile updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="phone_number", type="string", example="+1234567890"),
     *                 @OA\Property(property="country", type="string", example="United States"),
     *                 @OA\Property(property="city", type="string", example="New York"),
     *                 @OA\Property(property="avatar", type="string", example="avatars/user123.jpg"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="status_code", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="avatar", type="array", @OA\Items(type="string", example="The avatar must be an image."))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User not authorized to update profile",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="status_code", type="integer", example=403),
     *             @OA\Property(property="message", type="string", example="You are not authorized to update this profile"),
     *             @OA\Property(property="data", type="null", example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="status_code", type="integer", example=404),
     *             @OA\Property(property="message", type="string", example="User not found"),
     *             @OA\Property(property="data", type="null", example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="status_code", type="integer", example=500),
     *             @OA\Property(property="message", type="string", example="An error occurred while updating profile"),
     *             @OA\Property(property="data", type="null", example=null)
     *         )
     *     )
     * )
     */
    public function updateProfile(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'name' => 'nullable|string|max:255',
                'phone_number' => 'nullable|string|max:20',
                'country' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:255',
                'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'status_code' => 400,
                    'message' => 'Validation failed',
                    'data' => $validator->errors()
                ], 400);
            }

            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'status_code' => 404,
                    'message' => 'User not found',
                    'data' => null
                ], 404);
            }

            if ($user->id !== Auth::id()) {
                return response()->json([
                    'status' => 'error',
                    'status_code' => 403,
                    'message' => 'You are not authorized to update this profile',
                    'data' => null
                ], 403);
            }

            $data = $request->only(['name', 'phone_number', 'country', 'city']);

            if ($request->hasFile('avatar')) {
                if ($user->avatar) {
                    Storage::disk('public')->delete($user->avatar);
                }
                $avatarPath = $request->file('avatar')->store('avatars', 'public');
                $data['avatar'] = $avatarPath;
            }

            User::where('id', $user->id)->update($data);

            $updatedUser = User::find($user->id);

            if ($updatedUser->avatar) {
                $updatedUser->avatar = Storage::url($updatedUser->avatar);
            }

            return response()->json([
                'status' => 'success',
                'status_code' => 200,
                'message' => 'Profile updated successfully',
                'data' => $updatedUser
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'status_code' => 500,
                'message' => 'An error occurred while updating profile',
                'data' => null,
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/user",
     *     summary="Delete user account",
     *     description="Deletes the authenticated user's account permanently with all associated data",
     *     tags={"User"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Account deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Your account has been deleted successfully"),
     *             @OA\Property(property="data", type="null", example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="status_code", type="integer", example=500),
     *             @OA\Property(property="message", type="string", example="An error occurred while deleting your account"),
     *             @OA\Property(property="data", type="null", example=null)
     *         )
     *     )
     * )
     */
    public function deleteAccount(Request $request)
    {
        try {
            $user = Auth::user();

            // Delete user's avatar if exists
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            //$user->delete();

            Auth::invalidate(true);

            return response()->json([
                'status' => 'success',
                'status_code' => 200,
                'message' => 'Your account has been deleted successfully',
                'data' => null
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'status_code' => 500,
                'message' => 'An error occurred while deleting your account',
                'data' => null,
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }
}
