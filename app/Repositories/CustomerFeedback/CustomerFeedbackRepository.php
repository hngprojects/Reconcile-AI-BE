<?php

namespace App\Repositories\CustomerFeedback;

use LaravelEasyRepository\Repository;

interface CustomerFeedbackRepository extends Repository{
    public function store(array $data);
    public function findByEmail($email);
}
