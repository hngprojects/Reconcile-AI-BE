<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

namespace App\Http\Controllers;

use App\Mail\PartnerWelcomeMail;
use App\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactFormSubmitted;
use Illuminate\Support\Facades\Validator;

class PartnerController extends Controller
{
    /**
     * Handle the contact form submission
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function submit(Request $request)
    {
        // Validate the form data
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'business_name' => 'nullable|string|max:255',
            'service_interested' => 'required|string|max:500',
            'email' => 'required|email|max:255',
            'phone_number' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Store the contact submission
        $submission = Partner::create([
            'full_name' => $request->full_name,
            'business_name' => $request->business_name,
            'service_interested' => $request->service_interested,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
        ]);

        // Send notification email
        try {
            Mail::to(config('mail.admin_address'))->send(new PartnerWelcomeMail($submission));
        } catch (\Exception $e) {
            // Log the error but don't fail the request
            Log::error('Failed to send contact form email: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Thank you for your submission. We will contact you shortly.'
        ], 200);
    }
}
