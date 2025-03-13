<?php

namespace App\Services\NewsLetter;

use LaravelEasyRepository\BaseService;

interface NewsLetterService extends BaseService{

    public function subscribe($request);
    public function unsubscribe($request);
    public function onClick($request);
    public function onClickResubscribe($email);
}
