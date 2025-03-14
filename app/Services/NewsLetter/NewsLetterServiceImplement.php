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
            
            // Check if already subscribed
            $subscriber = $this->mainRepository->findByEmail($validated['email']);
            if ($subscriber && $subscriber->subscribed) {
                return $this->setCode(200)
                    ->setMessage("Already Subscribed")
                    ->setData([
                        'email' => $validated['email'],
                    ]);
            }
            
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
            
            // Check if already unsubscribed
            $subscriber = $this->mainRepository->findByEmail($validated['email']);
            if ($subscriber && !$subscriber->subscribed) {
                return $this->setCode(200)
                    ->setMessage("Already Unsubscribed")
                    ->setData([
                        'email' => $validated['email'],
                    ]);
            }
            
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

    public function onClick($email)
    {
        try {
            // Validate that the email is not empty and is a valid email format
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return redirect()->route('newsletter.result', [
                    'action' => 'unsubscribe',
                    'status' => 'invalid'
                ]);
            }

            // Check if email exists in the database
            try {
                $subscriber = $this->mainRepository->checkforsubscriber($email);
            } catch (Exception $e) {
                return redirect()->route('newsletter.result', [
                    'action' => 'unsubscribe',
                    'status' => 'invalid'
                ]);
            }

            // Check if already unsubscribed
            if (!$subscriber->subscribed) {
                return redirect()->route('newsletter.result', [
                    'action' => 'unsubscribe',
                    'status' => 'already'
                ]);
            }

            // Process unsubscription
            $unsubscribeResult = $this->mainRepository->unsubscribe($email);

            // Send confirmation email only if the status changed
            Mail::to($email)->queue(new UnsubscribeConfirmation($email));

            // Determine redirect status
            $status = $unsubscribeResult ? 'success' : 'error';

            return redirect()->route('newsletter.result', [
                'action' => 'unsubscribe',
                'status' => $status
            ]);
        } catch (Exception $e) {
            return redirect()->route('newsletter.result', [
                'action' => 'unsubscribe',
                'status' => 'error'
            ]);
        }
    }

    public function onClickResubscribe($email)
    {
        try {
            // Validate email format
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return redirect()->route('newsletter.result', [
                    'action' => 'resubscribe',
                    'status' => 'invalid'
                ]);
            }

            // Check if email exists in the database
            try {
                $subscriber = $this->mainRepository->checkforsubscriber($email);
            } catch (Exception $e) {
                return redirect()->route('newsletter.result', [
                    'action' => 'resubscribe',
                    'status' => 'invalid'
                ]);
            }

            // Check if already subscribed
            if ($subscriber->subscribed) {
                return redirect()->route('newsletter.result', [
                    'action' => 'resubscribe',
                    'status' => 'already'
                ]);
            }

            // Process resubscription
            $resubscribeResult = $this->mainRepository->resubscribe($email);

            // Send confirmation email only if the status changed
            Mail::to($email)->queue(new SubscriptionConfirmation($email));

            // Determine redirect status
            $status = $resubscribeResult ? 'success' : 'error';

            return redirect()->route('newsletter.result', [
                'action' => 'resubscribe',
                'status' => $status
            ]);
        } catch (Exception $e) {
            return redirect()->route('newsletter.result', [
                'action' => 'resubscribe',
                'status' => 'error'
            ]);
        }
    }
}