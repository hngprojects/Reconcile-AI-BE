<?php

namespace App\Repositories\ContactUs;

use LaravelEasyRepository\Repository;

interface ContactUsRepository extends Repository{

    public function creatContactMessge($data);
}
