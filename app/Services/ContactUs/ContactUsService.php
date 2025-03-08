<?php

namespace App\Services\ContactUs;

use LaravelEasyRepository\BaseService;

interface ContactUsService extends BaseService{

    public function saveContactMessage($request);
}
