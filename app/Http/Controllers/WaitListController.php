<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\WaitList;
use Illuminate\Http\Request;

class WaitListController extends Controller
{

    /**
     * @OA\Post(
     *     path="/api/v1/wait-list",
     *     summary="Add email to waitlist",
     *     description="Adds a new email address to the wait list",
     *     tags={"WaitList"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", description="Email address to add to waitlist")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Successfully joined wait list",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Successfully joined wait list")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Email is required")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        // Basic validation
        if (!$request->email) {
            return response()->json([
                "status" => false,
                'message' => 'Email is required'
            ], 400);
        }

        // Check if email is valid
        if (!filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                "status" => false,
                'message' => 'Please provide a valid email'
            ], 400);
        }

        // Check if email already exists
        if (WaitList::where('email', $request->email)->exists()) {
            return response()->json([
                "status" => false,
                'message' => 'Email already registered'
            ], 400);
        }

        // Save to database
        $waitlist = new WaitList();
        $waitlist->email = $request->email;
        $waitlist->save();

        // Return success response
        return response()->json([
            "status" => true,
            'message' => 'Successfully joined wait list'
        ], 201);
    }
}
