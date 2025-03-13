<?php

namespace App\Services\NewsLetter;

use LaravelEasyRepository\ServiceApi;
use App\Repositories\NewsLetter\NewsLetterRepository;
use Exception;
use Illuminate\Support\Facades\Mail;
use App\Mail\UnsubscribeConfirmation;
use App\Mail\SubscriptionConfirmation;

class NewsLetterServiceImplement extends ServiceApi implements NewsLetterService
{
    /**
     * don't change $this->mainRepository variable name
     * because used in extends service class
     */
    protected NewsLetterRepository $mainRepository;

    public function __construct(NewsLetterRepository $mainRepository)
    {
        $this->mainRepository = $mainRepository;
    }

    public function subscribe($request)
    {
        try {
            $validated = $request->validated();
            $subscribe = $this->mainRepository->subscribe($validated['email']);

            Mail::to($validated['email'])->queue(new SubscriptionConfirmation($validated['email']));

            return $this->setCode(200)
                ->setMessage("Subscription Successful")
                ->setData([
                    'email' => $subscribe['email'],
                ]);
        } catch (Exception $e) {
            return $this->setCode(400)
                ->setMessage("Subscription Failed")
                ->setError($e->getMessage());
        }
    }

    public function unsubscribe($request)
    {
        try {
            $validated = $request->validated();
            $unsubscribe = $this->mainRepository->unsubscribe($validated['email']);

            Mail::to($validated['email'])->queue(new UnsubscribeConfirmation($validated['email']));

            return $this->setCode(200)
                ->setMessage("Unsubscribed Successfully")
                ->setData([
                    'email' => $validated['email'],
                ]);
        } catch (Exception $e) {
            return $this->setCode(400)
                ->setMessage("Unsubscription Failed")
                ->setError($e->getMessage());
        }
    }
}
