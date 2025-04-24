<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ManualReconciliationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'matches' => 'required|array',
            'matches.*.ledger' => 'required|string|uuid',
            'matches.*.statement' => 'required|string|uuid',
            'matches.*.matched_by' => 'required|string',
            'matches.*.score' => 'required|integer',
            'matches.*.action' => 'required|string'
        ];
    }
}
