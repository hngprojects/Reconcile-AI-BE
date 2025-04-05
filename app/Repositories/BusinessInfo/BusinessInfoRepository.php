<?php

namespace App\Repositories\BusinessInfo;

use App\Models\BusinessInfo;
use LaravelEasyRepository\Repository;

interface BusinessInfoRepository extends Repository{

    public function createBusinessInfo(array $data): BusinessInfo;
}
