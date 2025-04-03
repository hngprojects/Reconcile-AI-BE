<?php

namespace App\Repositories\Business;

use LaravelEasyRepository\Repository;

interface BusinessRepository extends Repository{

    public function createBusiness(array $data);
    public function updateBusiness(int $id, array $data);
    public function getByUserId(int $userId);
}
