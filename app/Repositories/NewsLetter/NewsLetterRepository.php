<?php

namespace App\Repositories\NewsLetter;

use LaravelEasyRepository\Repository;

interface NewsLetterRepository extends Repository{

    public function subscribe($email);
    public function unsubscribe($email);
}
