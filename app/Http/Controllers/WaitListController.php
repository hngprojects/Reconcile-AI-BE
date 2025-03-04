<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Waitlist;

class WaitListController extends Controller
{
    public function store(Request $request)
    {
        // Basic validation
        if (!$request->email) {
            return response()->json([
                'message' => 'Email is required'
            ], 400);
        }

        // Check if email is valid
        if (!filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'message' => 'Please provide a valid email'
            ], 400);
        }

        // Check if email already exists
        if (Waitlist::where('email', $request->email)->exists()) {
            return response()->json([
                'message' => 'Email already registered'
            ], 400);
        }

        // Save to database
        $waitlist = new Waitlist();
        $waitlist->email = $request->email;
        $waitlist->save();

        // Return success response
        return response()->json([
            'message' => 'Successfully joined wait list'
        ], 201);
    }
}
