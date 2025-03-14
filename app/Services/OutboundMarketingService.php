<?php

namespace App\Services;

use App\Mail\OutboundMarketingMail;
use App\Models\NewsLetter;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

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
            $marketing = NewsLetter::create($data);
            Mail::to($data['email'])->send(new OutboundMarketingMail($marketing));

            return [true, $marketing];
        } catch (\Exception $e) {
            return [false, 'Failed to create marketing campaign'];
        }
    }
}
