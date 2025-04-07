<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="AccountSetupRequest",
 *     type="object",
 *     required={
 *         "business_name", "business_type", "currency", "reporting_year",
 *         "bank_name", "account_name", "account_number", "opening_balance", "ledger_types"
 *     },
 *     @OA\Property(property="business_name", type="string", example="Dayo & Co."),
 *     @OA\Property(property="business_type", type="string", example="Retail"),
 *     @OA\Property(property="currency", type="string", example="NGN"),
 *     @OA\Property(property="reporting_year", type="string", example="January - December"),
 *     @OA\Property(property="bank_name", type="string", example="Access Bank"),
 *     @OA\Property(property="account_name", type="string", example="Dayo Business"),
 *     @OA\Property(property="account_number", type="string", example="1234567890"),
 *     @OA\Property(property="opening_balance", type="number", format="float", example=10000),
 *     @OA\Property(
 *         property="ledger_types",
 *         type="array",
 *         @OA\Items(type="string", enum={"general", "vendor", "customer"})
 *     )
 * )
 */

class SetupAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Business Info
            'business_name' => 'required|string|max:255',
            'business_type' => 'required|string|max:255',
            'currency' => 'required|string|max:3',
            'reporting_year' => 'required|string|in:January - December,April - March,July - June',
            
            // Bank Account
            'bank_name' => 'required|string|max:255',
            'account_name' => 'required|string|max:255',
            'account_number' => [
                'required','string','max:255',
                /* Rule::unique('bank_accounts')->where(function ($query) {
                    return $query->where('user_id', $this->user()->id);
                }) */
            ],
            'opening_balance' => 'required|numeric|min:0',
            
            // Ledger Types (from the UI checkboxes)
            'ledger_types' => 'required|array',
            'ledger_types.*' => 'in:general,vendor,customer'
        ];
    }

    public function messages(): array
    {
        return [
            'ledger_types.required' => 'Please select at least one ledger type',
            'ledger_types.*.in' => 'Invalid ledger type selected'
        ];
    }
}
