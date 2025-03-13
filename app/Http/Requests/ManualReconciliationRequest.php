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
            'ledger' => 'required',
            'statement' => 'required',
            'action' => ['required', Rule::in(['match', 'unmatch'])],
            'ledger.date' => 'required|string',
            'ledger.description' => 'required|string',
            'ledger.amount' => 'required|integer',
            'statement.date' => 'required|string',
            'statement.description' => 'required|string',
            'statement.amount' => 'required|integer',
        ];
    }
}
