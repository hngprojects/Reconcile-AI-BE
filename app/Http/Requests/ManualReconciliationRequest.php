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
            'ledgers.*.Date' => 'required|string',
            'ledgers.*.Person' => 'required|string',
            'ledgers.*.Amount' => 'required',
            'statements.*.Date' => 'required|string',
            'statements.*.Person' => 'required|string',
            'statements.*.Amount' => 'required',
        ];
    }
}
