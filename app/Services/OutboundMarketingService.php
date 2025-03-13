<?php

namespace App\Services;

use App\Models\OutboundMarketing;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class OutboundMarketingService
{
    public function validateInput(array $data): array
    {
        $validator = Validator::make($data, [
            'full_name' => 'required|string|max:255',
            'business_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone_number' => 'required|string',
        ]);

        if ($validator->fails()) {
            return [false, $validator->errors()];
        }

        return [true, $validator->validated()];
    }

    public function createOutboundMarketing(array $data): array
    {
        try {
            $marketing = OutboundMarketing::create($data);
            return [true, $marketing];
        } catch (\Exception $e) {
            return [false, 'Failed to create marketing campaign'];
        }
    }
}
