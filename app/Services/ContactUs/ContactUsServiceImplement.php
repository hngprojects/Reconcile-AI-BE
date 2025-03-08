<?php

namespace App\Services\ContactUs;

use Exception;
use LaravelEasyRepository\ServiceApi;
use App\Repositories\ContactUs\ContactUsRepository;

class ContactUsServiceImplement extends ServiceApi implements ContactUsService
{
    /**
     * don't change $this->mainRepository variable name
     * because used in extends service class
     */
    protected ContactUsRepository $mainRepository;

    public function __construct(ContactUsRepository $mainRepository)
    {
        $this->mainRepository = $mainRepository;
    }

    public function saveContactMessage($request)
    {
        try {
            $validated = $request->validated();
            $contact = $this->mainRepository->creatContactMessge($validated);

            return $this->setCode(200)
                ->setMessage("Contact message sent Successful");
        } catch (Exception $e) {
            return $this->setCode(400)
                ->setMessage("Contact message sent Failed")
                ->setError($e->getMessage());
        }
    }
}
