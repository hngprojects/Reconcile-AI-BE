<?php

namespace App\Repositories\NewsLetter;

use LaravelEasyRepository\Repository;

interface NewsLetterRepository extends Repository{

    public function subscribe($email);
    public function unsubscribe($email);
    public function checkforsubscriber($email);
    public function resubscribe($email);
}
