<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JobApplication;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class JobApplicationController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/job-application",
     *     summary="Submit a job application",
     *     description="Handles the submission of a job application. Validates the input, stores the resume file, and saves the application to the database.",
     *     tags={"Job Applications"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name", "email", "resume"},
     *                 @OA\Property(property="name", type="string", example="John Doe", description="Full name of the applicant"),
     *                 @OA\Property(property="email", type="string", format="email", example="john@example.com", description="Email address of the applicant"),
     *                 @OA\Property(property="resume", type="string", format="binary", description="Resume file (PDF, DOC, DOCX, max 2MB)"),
     *                 @OA\Property(property="linkedin", type="string", example="https://linkedin.com/in/johndoe", description="LinkedIn profile URL"),
     *                 @OA\Property(property="compensation", type="number", format="float", example="75000", description="Expected compensation"),
     *                 @OA\Property(property="experience", type="integer", example="5", description="Years of experience")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Application submitted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="status_code", type="integer", example=201),
     *             @OA\Property(property="message", type="string", example="Application submitted successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="resume", type="string", example="resumes/abcdef123456.pdf"),
     *                 @OA\Property(property="linkedin", type="string", example="https://linkedin.com/in/johndoe"),
     *                 @OA\Property(property="compensation", type="number", example=75000),
     *                 @OA\Property(property="experience", type="integer", example=5),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(property="id", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="status_code", type="integer", example=422),
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="name", type="array", @OA\Items(type="string", example="The name field is required.")),
     *                 @OA\Property(property="email", type="array", @OA\Items(type="string", example="The email has already been taken.")),
     *                 @OA\Property(property="resume", type="array", @OA\Items(type="string", example="The resume must be a file of type: pdf, doc, docx."))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="status_code", type="integer", example=500),
     *             @OA\Property(property="message", type="string", example="Failed to submit application."),
     *             @OA\Property(property="data", type="null", example=null)
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        // Validate request
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:job_applications,email',
            'resume' => 'required|file|mimes:pdf,doc,docx|max:2048',
            'linkedin' => 'nullable|url',
            'compensation' => 'nullable|numeric|min:0',
            'experience' => 'nullable|integer|min:0',
        ]);

        // Store resume file
        $path = $request->file('resume')->store('resumes', 'public');

        // Save to database
        $jobApplication = JobApplication::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'resume' => $path,
            'linkedin' => $validated['linkedin'] ?? null,
            'compensation' => $validated['compensation'] ?? null,
            'experience' => $validated['experience'] ?? null,
        ]);

        return response()->json([
            'status' => 'success',
            'status_code' => 201,
            'message' => 'Application submitted successfully',
            'data' => $jobApplication
        ], 201);
    }
}
