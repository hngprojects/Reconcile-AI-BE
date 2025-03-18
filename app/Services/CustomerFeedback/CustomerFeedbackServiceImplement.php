<?php

namespace App\Services\CustomerFeedback;

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
            
            // Store feedback in database
            $feedback = $this->mainRepository->store([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'message' => $validated['message'] ?? null,
                'request_type' => $validated['request_type'] ?? 'Feedback'
            ]);

            // Send confirmation email
            Mail::to($feedback->email)->queue(new FeedbackMail($feedback));

            return $this->setCode(200)
                ->setMessage("Feedback Submitted Successfully")
                ->setData([
                    'id' => $feedback->id,
                    'name' => $feedback->name,
                    'email' => $feedback->email,
                    'created_at' => $feedback->created_at
                ]);
        } catch (Exception $e) {
            return $this->setCode(400)
                ->setMessage("Feedback Submission Failed")
                ->setError($e->getMessage());
        }
    }
}