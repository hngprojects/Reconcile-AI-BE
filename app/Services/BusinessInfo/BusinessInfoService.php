<?php

namespace App\Services\BusinessInfo;

use LaravelEasyRepository\BaseService;
use Illuminate\Http\Request;

interface BusinessInfoService extends BaseService{

    public function setupBusinessInfo(Request $request): array;
    public function updateBusinessInfo(string $id, Request $request): array;
}
