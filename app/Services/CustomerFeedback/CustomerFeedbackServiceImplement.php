<?php

namespace App\Services\CustomerFeedback;

use App\Mail\AdminFeedbackMail;
use App\Mail\FeedbackMail;
use LaravelEasyRepository\ServiceApi;
use App\Repositories\CustomerFeedback\CustomerFeedbackRepository;
use Illuminate\Support\Facades\Mail;
use Exception;

class CustomerFeedbackServiceImplement extends ServiceApi implements CustomerFeedbackService{

    /**
     * set title message api for CRUD
     * @param string $title
     */
     protected string $title = "Customer Feedback";
     /**
     * uncomment this to override the default message
     * protected string $create_message = "";
     * protected string $update_message = "";
     * protected string $delete_message = "";
     */

     /**
     * don't change $this->mainRepository variable name
     * because used in extends service class
     */
     protected CustomerFeedbackRepository $mainRepository;

    public function __construct(CustomerFeedbackRepository $mainRepository)
    {
      $this->mainRepository = $mainRepository;
    }

    // Define your custom methods :)
    public function createCustomerFeedback($request)
    {
        try {
            $validated = $request->validated();

             // Handle file upload if present
             $filePath = null;
             if ($request->hasFile('file')) {
                 $file = $request->file('file');
                 $fileName = time() . '_' . $file->getClientOriginalName();
                 $filePath = $file->storeAs('feedback_attachments', $fileName, 'public');
             }
            
            // Store feedback in database
            $feedback = $this->mainRepository->store([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'subject' => $validated['subject'] ?? null,
                'message' => $validated['message'] ?? null,
                'file_path' => $filePath,
                'request_type' => $validated['request_type'] ?? 'Feedback'
            ]);

            // Send confirmation email
            Mail::to($feedback->email)->queue(new FeedbackMail($feedback));
            Mail::to(config('mail.admin_address'))->queue(new AdminFeedbackMail($feedback));

            return $this->setCode(200)
                ->setMessage("Feedback Submitted Successfully")
                ->setData([
                    'id' => $feedback->id,
                    'name' => $feedback->name,
                    'email' => $feedback->email,
                    'subject' => $feedback->subject
                ]);
        } catch (Exception $e) {
            return $this->setCode(400)
                ->setMessage("Feedback Submission Failed")
                ->setError($e->getMessage());
        }
    }
}