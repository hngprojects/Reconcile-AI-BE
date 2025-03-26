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
            'ledgers' => 'required|array',
            'statements' => 'required|array',
            'action' => ['required', Rule::in(['match', 'unmatch'])],
            'ledgers.*' => 'string',
            'statements.*' => 'string',
        ];
    }
}
